<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPullRequestReview;
use App\Models\Repository;
use App\Models\PullRequest;
use App\Models\PullRequestFile;
use App\Models\PullRequestReview;
use App\Services\GitHubService;
use App\Services\PullRequestReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class GitHubWebhookController extends Controller
{
    protected GitHubService $githubService;
    protected PullRequestReviewService $reviewService;

    public function __construct(
        GitHubService $githubService,
        PullRequestReviewService $reviewService
    ) {
        $this->githubService = $githubService;
        $this->reviewService = $reviewService;
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            // Verify GitHub webhook signature
            if (!$this->verifyGitHubSignature($request)) {
                Log::warning('GitHub webhook: Invalid signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $event = $request->header('X-GitHub-Event');
            $payload = $request->all();

            Log::info('GitHub webhook received', [
                'event' => $event,
                'action' => $payload['action'] ?? 'no_action',
                'repository' => $payload['repository']['full_name'] ?? 'unknown'
            ]);

            switch ($event) {
                case 'pull_request':
                    return $this->handlePullRequestEvent($payload);
                case 'push':
                    return $this->handlePushEvent($payload);
                default:
                    Log::info('GitHub webhook: Unhandled event type', ['event' => $event]);
                    return response()->json(['message' => 'Event not handled'], 200);
            }

        } catch (\Exception $e) {
            Log::error('GitHub webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    protected function handlePullRequestEvent(array $payload): JsonResponse
    {
        $action = $payload['action'];
        $prData = $payload['pull_request'];
        $repoData = $payload['repository'];

        // Find the repository in our system
        $repository = Repository::where('github_repo_id', $repoData['id'])->first();

        if (!$repository) {
            Log::info('GitHub webhook: Repository not found in system', [
                'github_repo_id' => $repoData['id'],
                'full_name' => $repoData['full_name']
            ]);
            return response()->json(['message' => 'Repository not tracked'], 200);
        }

        switch ($action) {
            case 'opened':
                return $this->handlePullRequestOpened($prData, $repository);
            case 'synchronize': // New commits pushed
                return $this->handlePullRequestSynchronized($prData, $repository);
            case 'closed':
                return $this->handlePullRequestClosed($prData, $repository);
            case 'reopened':
                return $this->handlePullRequestReopened($prData, $repository);
            default:
                Log::info('GitHub webhook: Unhandled PR action', ['action' => $action]);
                return response()->json(['message' => 'PR action not handled'], 200);
        }
    }

    protected function handlePullRequestOpened(array $prData, Repository $repository): JsonResponse
    {
        try {
            // Create or update pull request record
            $pullRequest = $this->createOrUpdatePullRequest($prData, $repository);

            // Fetch and store files changed in this PR
            $this->fetchAndStorePullRequestFiles($pullRequest, $repository);

            // Replace this line:
            // $this->reviewService->triggerAutomaticReview($pullRequest);
            // With:
            $this->triggerAIReview($pullRequest, $repository);

            return response()->json([
                'message' => 'Pull request processed',
                'ai_review_queued' => $repository->auto_review_enabled ?? true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling opened pull request', [
                'pr_number' => $prData['number'],
                'repository_id' => $repository->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to process pull request'], 500);
        }
    }

    protected function handlePullRequestSynchronized(array $prData, Repository $repository): JsonResponse
    {
        try {
            // Update pull request record
            $pullRequest = $this->createOrUpdatePullRequest($prData, $repository);

            // Update files (remove old ones and fetch new ones)
            $pullRequest->files()->delete();
            $this->fetchAndStorePullRequestFiles($pullRequest, $repository);

            // Replace this line:
            // $this->reviewService->triggerAutomaticReview($pullRequest);
            // With:
            $this->triggerAIReview($pullRequest, $repository);

            return response()->json([
                'message' => 'Pull request synchronized',
                'ai_review_queued' => $repository->auto_review_enabled ?? true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling synchronized pull request', [
                'pr_number' => $prData['number'],
                'repository_id' => $repository->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to synchronize pull request'], 500);
        }
    }

    protected function handlePullRequestClosed(array $prData, Repository $repository): JsonResponse
    {
        try {
            $pullRequest = PullRequest::where('github_pr_id', $prData['id'])
                ->where('repository_id', $repository->id)
                ->first();

            if ($pullRequest) {
                $pullRequest->update([
                    'state' => $prData['merged'] ? 'merged' : 'closed',
                    'closed_at' => now(),
                    'merged_at' => $prData['merged'] ? now() : null,
                ]);
            }

            return response()->json(['message' => 'Pull request closed'], 200);

        } catch (\Exception $e) {
            Log::error('Error handling closed pull request', [
                'pr_number' => $prData['number'],
                'repository_id' => $repository->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to close pull request'], 500);
        }
    }

    protected function handlePullRequestReopened(array $prData, Repository $repository): JsonResponse
    {
        try {
            $pullRequest = $this->createOrUpdatePullRequest($prData, $repository);

            // Replace this line:
            // $this->reviewService->triggerAutomaticReview($pullRequest);
            // With:
            $this->triggerAIReview($pullRequest, $repository);

            return response()->json([
                'message' => 'Pull request reopened',
                'ai_review_queued' => $repository->auto_review_enabled ?? true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling reopened pull request', [
                'pr_number' => $prData['number'],
                'repository_id' => $repository->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to reopen pull request'], 500);
        }
    }

    protected function handlePushEvent(array $payload): JsonResponse
    {
        // Handle push events if needed (e.g., for branch protection or CI/CD)
        Log::info('GitHub webhook: Push event received', [
            'ref' => $payload['ref'],
            'repository' => $payload['repository']['full_name']
        ]);

        return response()->json(['message' => 'Push event processed'], 200);
    }

    protected function createOrUpdatePullRequest(array $prData, Repository $repository): PullRequest
    {
        return PullRequest::updateOrCreate(
            [
                'github_pr_id' => $prData['id'],
                'repository_id' => $repository->id,
            ],
            [
                'title' => $prData['title'],
                'body' => $prData['body'],
                'github_pr_number' => $prData['number'],
                'state' => $prData['state'] === 'closed' && $prData['merged'] ? 'merged' : $prData['state'],
                'html_url' => $prData['html_url'],
                'head_sha' => $prData['head']['sha'],
                'base_sha' => $prData['base']['sha'],
                'head_branch' => $prData['head']['ref'],
                'base_branch' => $prData['base']['ref'],
                'author_username' => $prData['user']['login'],
                'author_avatar_url' => $prData['user']['avatar_url'],
                'mergeable' => $prData['mergeable'] ?? null,
                'user_id' => $repository->user_id,
                'webhook_data' => $prData,
            ]
        );
    }

    protected function fetchAndStorePullRequestFiles(PullRequest $pullRequest, Repository $repository): void
    {
        try {
            $user = $repository->user;
            if (!$user || !$user->github_token) {
                Log::warning('No GitHub token available for fetching PR files', [
                    'repository_id' => $repository->id,
                    'pr_id' => $pullRequest->id
                ]);
                return;
            }

            $files = $this->githubService->getPullRequestFiles(
                $user->github_token,
                $repository->full_name,
                $pullRequest->github_pr_number
            );

            foreach ($files as $file) {
                PullRequestFile::create([
                    'filename' => $file['filename'],
                    'status' => $file['status'],
                    'additions' => $file['additions'],
                    'deletions' => $file['deletions'],
                    'changes' => $file['changes'],
                    'blob_url' => $file['blob_url'] ?? null,
                    'raw_url' => $file['raw_url'] ?? null,
                    'patch' => $file['patch'] ?? null,
                    'previous_filename' => $file['previous_filename'] ?? null,
                    'language' => $this->detectLanguage($file['filename']),
                    'pull_request_id' => $pullRequest->id,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error fetching PR files', [
                'pr_id' => $pullRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function detectLanguage(string $filename): ?string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $languageMap = [
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'py' => 'python',
            'java' => 'java',
            'cpp' => 'cpp',
            'c' => 'c',
            'cs' => 'csharp',
            'go' => 'go',
            'rb' => 'ruby',
            'swift' => 'swift',
            'kt' => 'kotlin',
            'rs' => 'rust',
            'vue' => 'vue',
            'jsx' => 'jsx',
            'tsx' => 'tsx',
        ];

        return $languageMap[strtolower($extension)] ?? null;
    }

    protected function verifyGitHubSignature(Request $request): bool
    {
        $secret = config('services.github.webhook_secret');
        if (!$secret) {
            Log::warning('GitHub webhook secret not configured');
            return true;
        }
        $payload = $request->getContent();

        // Try SHA256 first
        $signature256 = $request->header('X-Hub-Signature-256');
        if ($signature256) {
            $expected256 = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected256, $signature256);
        }

        // Fallback to SHA1 if SHA256 not present
        $signature1 = $request->header('X-Hub-Signature');
        if ($signature1) {
            $expected1 = 'sha1=' . hash_hmac('sha1', $payload, $secret);
            return hash_equals($expected1, $signature1);
        }
        Log::info('Webhook headers', $request->headers->all());
        Log::info('Webhook payload', [$request->getContent()]);
        return false;
    }

    protected function triggerAIReview(PullRequest $pullRequest, Repository $repository): void
    {
        try {
            if (!($repository->auto_review_enabled ?? true)) {
                Log::info('Auto-review disabled for repository', [
                    'repository_id' => $repository->id,
                    'pr_id' => $pullRequest->id
                ]);
                return;
            }

            $codeFiles = $pullRequest->files()
                ->whereNotNull('language')
                ->whereNotIn('status', ['removed'])
                ->count();

            if ($codeFiles === 0) {
                Log::info('No code files found for AI review', [
                    'pr_id' => $pullRequest->id
                ]);
                return;
            }

            // Dispatch AI review job directly
            ProcessPullRequestReview::dispatch($pullRequest);

            Log::info('AI review queued for pull request', [
                'pr_id' => $pullRequest->id,
                'github_pr_number' => $pullRequest->github_pr_number,
                'files_count' => $codeFiles
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger AI review', [
                'pr_id' => $pullRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

}
