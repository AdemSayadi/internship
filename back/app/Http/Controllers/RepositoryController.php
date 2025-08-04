<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RepositoryController extends Controller
{
    protected GitHubService $githubService;

    public function __construct(GitHubService $githubService)
    {
        $this->middleware('auth:sanctum');
        $this->githubService = $githubService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                Log::error('Repository index: No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $repositories = $user->repositories()->with('codeSubmissions')->get();

            return response()->json([
                'success' => true,
                'repositories' => $repositories
            ]);

        } catch (\Exception $e) {
            Log::error('Repository index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user() ? $request->user()->id : 'null'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch repositories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'nullable|url',
                'provider' => 'required|in:github,gitlab,manual',
                'github_repo_id' => 'required_if:provider,github|numeric',
                'full_name' => 'nullable|string|max:512',
                'is_private' => 'sometimes|boolean'
            ]);

            // Check if GitHub repo already exists
            if ($validated['provider'] === 'github' && isset($validated['github_repo_id'])) {
                $exists = Repository::where('user_id', $user->id)
                    ->where('github_repo_id', $validated['github_repo_id'])
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This GitHub repository is already connected'
                    ], 409);
                }
            }

            $repository = Repository::create([
                'name' => $validated['name'],
                'url' => $validated['url'],
                'provider' => $validated['provider'],
                'user_id' => $user->id,
                'github_repo_id' => $validated['github_repo_id'] ?? null,
                'full_name' => $validated['full_name'] ?? null,
                'is_private' => $validated['is_private'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'repository' => $repository->load('codeSubmissions')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Repository store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create repository',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function batchStore(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Add detailed validation
            $request->validate([
                'repositories' => 'required|array|min:1',
                'repositories.*.name' => 'required|string|max:255',
                'repositories.*.url' => 'nullable|url',
                'repositories.*.provider' => 'required|in:github,gitlab,manual',
                'repositories.*.github_repo_id' => 'required_if:repositories.*.provider,github|numeric',
                'repositories.*.full_name' => 'nullable|string|max:512',
                'repositories.*.private' => 'sometimes|boolean'
            ]);

            $created = [];
            $skipped = [];

            foreach ($request->repositories as $repoData) {
                try {
                    // Check for existing GitHub repo
                    if ($repoData['provider'] === 'github') {
                        $exists = Repository::where('user_id', $user->id)
                            ->where('github_repo_id', $repoData['github_repo_id'])
                            ->exists();

                        if ($exists) {
                            $skipped[] = $repoData['full_name'] ?? $repoData['name'];
                            continue;
                        }
                    }

                    $repo = Repository::create([
                        'user_id' => $user->id,
                        'name' => $repoData['name'],
                        'url' => $repoData['url'] ?? null,
                        'provider' => $repoData['provider'],
                        'github_repo_id' => $repoData['github_repo_id'] ?? null,
                        'full_name' => $repoData['full_name'] ?? null,
                        'is_private' => $repoData['private'] ?? false,
                    ]);

                    $created[] = $repo;
                } catch (\Exception $e) {
                    Log::error('Error creating repository', [
                        'data' => $repoData,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully processed repositories',
                'added' => count($created),
                'skipped' => count($skipped),
                'repositories' => $created,
                'skipped_repositories' => $skipped
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Repository batch store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create repositories: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function show(Request $request, Repository $repository): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $repository->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'repository' => $repository->load(['codeSubmissions.reviews'])
            ]);

        } catch (\Exception $e) {
            Log::error('Repository show error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch repository'
            ], 500);
        }
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $repository->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Prevent editing GitHub repos (should be managed via GitHub)
            if ($repository->provider === 'github') {
                return response()->json([
                    'success' => false,
                    'message' => 'GitHub repositories cannot be edited here'
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'url' => 'sometimes|nullable|url',
                'provider' => 'sometimes|in:github,gitlab,manual',
            ]);

            $repository->update($validated);

            return response()->json([
                'success' => true,
                'repository' => $repository->fresh()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Repository update error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update repository'
            ], 500);
        }
    }

    public function destroy(Request $request, Repository $repository): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $repository->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $repository->delete();

            return response()->json([
                'success' => true,
                'message' => 'Repository deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Repository destroy error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete repository'
            ], 500);
        }
    }

    public function submissions(Request $request, Repository $repository): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user || $repository->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $submissions = $repository->codeSubmissions()
                ->with(['reviews', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'submissions' => $submissions
            ]);

        } catch (\Exception $e) {
            Log::error('Repository submissions error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch submissions'
            ], 500);
        }
    }

    public function fetchGithubRepos(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->github_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No GitHub token found. Please connect your GitHub account.',
                ], 400);
            }

            $repos = $this->githubService->getUserRepos($user->github_token);

            return response()->json([
                'success' => true,
                'repositories' => $repos
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub repo fetch error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GitHub repositories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
