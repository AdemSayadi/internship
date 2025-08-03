<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RepositoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Debug authentication
            $user = $request->user();
            if (!$user) {
                Log::error('Repository index: No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            Log::info('Repository index: User authenticated', ['user_id' => $user->id]);

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
            ]);

            $repository = Repository::create([
                'name' => $validated['name'],
                'url' => $validated['url'],
                'provider' => $validated['provider'],
                'user_id' => $user->id,
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
}
