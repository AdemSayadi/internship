<?php

use App\Http\Controllers\AIReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\CodeSubmissionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Middleware\HandleCors;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    // GitHub OAuth routes
    Route::get('/github', [AuthController::class, 'redirectToGithub']);
});
// Public webhook endpoint (no authentication required)
Route::post('/webhooks/github', [GitHubWebhookController::class, 'handle']);

// Public password setting for GitHub users (doesn't require authentication)
Route::post('/auth/set-password', [AuthController::class, 'setPassword']);

// Protected routes requiring authentication
Route::middleware('auth:sanctum')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        //Account Management
        Route::delete('/github', [AuthController::class, 'disconnectGithub']);
        Route::post('/password', [AuthController::class, 'setPassword']);
    });

    // Repository management - NOW PROTECTED
    Route::apiResource('repositories', RepositoryController::class);
    Route::get('repositories/{repository}/submissions/', [RepositoryController::class, 'submissions']);
    // Fetch repositories from GitHub
    Route::get('/github/fetch-repos', [RepositoryController::class, 'fetchGithubRepos']);
    Route::post('/repositories/batch', [RepositoryController::class, 'batchStore']);

    // Code submission management
    Route::apiResource('code-submissions', CodeSubmissionController::class);
    Route::get('code-submissions/{id}/reviews', [CodeSubmissionController::class, 'reviews']);


    // Pull Request management
    Route::prefix('pull-requests')->group(function () {
        Route::get('/', [PullRequestController::class, 'index']);
        Route::get('/statistics', [PullRequestController::class, 'statistics']);
        Route::get('/{id}', [PullRequestController::class, 'show']);
        Route::get('/{id}/reviews', [PullRequestController::class, 'reviews']);
        Route::post('/{id}/reviews', [PullRequestController::class, 'createReview']);
        Route::post('/{id}/trigger-review', [PullRequestController::class, 'triggerReview']);
        Route::get('/repository/{repositoryId}', [PullRequestController::class, 'getByRepository']);
    });

    // Review management
    Route::apiResource('reviews', ReviewController::class);
    Route::get('reviews/statistics', [ReviewController::class, 'statistics']);

    // Notification management
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::delete('notifications/clear-read', [NotificationController::class, 'clearRead']);

    // Dashboard/Statistics routes
//    Route::prefix('dashboard')->group(function () {
//        Route::get('/stats', function () {
//            $user = auth()->user();
//            return response()->json([
//                'success' => true,
//                'stats' => [
//                    'repositories' => $user->repositories()->count(),
//                    'submissions' => $user->codeSubmissions()->count(),
//                    'pull_requests' => \App\Models\PullRequest::whereHas('repository', function ($q) use ($user) {
//                        $q->where('user_id', $user->id);
//                    })->count(),
//                    'reviews' => $user->codeSubmissions()->withCount('reviews')->get()->sum('reviews_count'),
//                    'notifications' => $user->notifications()->where('read', false)->count(),
//                ]
//            ]);
//        });
//    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
    });

        // AI Review Routes
        Route::prefix('ai-review')->group(function () {
            // Code Submissions
            Route::post('code-submissions/{codeSubmission}/review', [AIReviewController::class, 'reviewCodeSubmission']);
            Route::get('code-submissions/{codeSubmission}/review', [AIReviewController::class, 'getCodeSubmissionReview']);
            Route::post('code-submissions/batch-review', [AIReviewController::class, 'batchReviewCodeSubmissions']);

            // Pull Requests
            Route::post('pull-requests/{pullRequest}/review', [AIReviewController::class, 'reviewPullRequest']);
            Route::get('pull-requests/{pullRequest}/reviews', [AIReviewController::class, 'getPullRequestReview']);
            Route::get('pull-requests/{pullRequest}/ai-review', [AIReviewController::class, 'getAIReview']);

            // Statistics
            Route::get('stats', [AIReviewController::class, 'getReviewStats']);
        });
    });


// Test routes for development and debugging
    Route::middleware('auth:sanctum')->prefix('test')->group(function () {

        // Test 4: Java Sample Submission (object-oriented example)
        Route::post('create-sample-submission', function () {
            $user = auth()->user();

            $repository = $user->repositories()->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'name' => 'test-repo-java',
                'url' => 'https://github.com/testuser/test-repo-java',
                'provider' => 'github',
                'full_name' => 'testuser/test-repo-java',
                'is_private' => false,
                'webhook_enabled' => true,
            ]);

            $submission = \App\Models\CodeSubmission::create([
                'title' => 'Java Student Grade Calculator',
                'language' => 'java',
                'code_content' => 'import java.util.*;

class Student {
    String name;
    List<Integer> grades;

    public Student(String name, List<Integer> grades) {
        this.name = name;
        this.grades = grades;
    }

    public double averageGrade() {
        return grades.stream().mapToInt(Integer::intValue).average().orElse(0.0);
    }

    public String getResult() {
        double avg = averageGrade();
        return avg >= 60 ? "Pass" : "Fail";
    }
}

public class Main {
    public static void main(String[] args) {
        Student s = new Student("Alice", Arrays.asList(75, 82, 91, 68));
        System.out.println(s.name + " => " + s.getResult());
    }
}',
                'file_path' => 'src/Main.java',
                'repository_id' => $repository->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Java submission created',
                'submission' => $submission
            ]);
        });


        // Test 2: Trigger AI review manually
        Route::post('trigger-review/{submission}', function (\App\Models\CodeSubmission $submission) {
            // Verify ownership
            if ($submission->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Dispatch the review job
            \App\Jobs\ProcessCodeSubmissionReview::dispatch($submission);

            return response()->json([
                'message' => 'Review job dispatched',
                'submission_id' => $submission->id
            ]);
        });

        // Test 3: Check review status
        Route::get('review-status/{submission}', function (\App\Models\CodeSubmission $submission) {
            if ($submission->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $reviews = $submission->reviews()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'submission' => $submission->only(['id', 'title', 'language']),
                'reviews' => $reviews
            ]);
        });

        // Test 4: Test AI service directly
        Route::post('test-ai-service', function () {
            $sampleCode = '<?php
function getUserById($id) {
    $pdo = new PDO("mysql:host=localhost;dbname=test", "user", "password");
    $query = "SELECT * FROM users WHERE id = " . $id;
    $result = $pdo->query($query);
    return $result->fetch();
}';

            try {
                $aiService = new \App\Services\AICodeReviewService();

                // Create a temporary submission for testing
                $tempSubmission = new \App\Models\CodeSubmission([
                    'title' => 'Test Security Function',
                    'language' => 'php',
                    'code_content' => $sampleCode,
                    'user_id' => auth()->id(),
                ]);

                // This won't save to DB, just test the AI service
                $result = $aiService->reviewCodeSubmission($tempSubmission);

                return response()->json([
                    'message' => 'AI service test completed',
                    'result' => $result
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'AI service failed',
                    'message' => $e->getMessage()
                ], 500);
            }
        });

        // Test 5: Create sample pull request
        Route::post('create-sample-pr', function () {
            $user = auth()->user();

            $repository = $user->repositories()->first();
            if (!$repository) {
                return response()->json(['error' => 'No repository found. Create one first.'], 404);
            }

            // Create sample pull request
            $pr = \App\Models\PullRequest::create([
                'title' => 'Add new payment processor',
                'body' => 'This PR adds support for a new payment processor with improved security.',
                'github_pr_id' => rand(1000000, 9999999),
                'github_pr_number' => rand(1, 999),
                'state' => 'open',
                'html_url' => 'https://github.com/test/test/pull/' . rand(1, 999),
                'head_sha' => 'abc123def456',
                'base_sha' => 'def456abc123',
                'head_branch' => 'feature/new-payment',
                'base_branch' => 'main',
                'author_username' => 'testuser',
                'author_avatar_url' => 'https://github.com/testuser.png',
                'repository_id' => $repository->id,
                'user_id' => $user->id,
            ]);

            // Add sample files
            $files = [
                [
                    'filename' => 'src/PaymentProcessor.php',
                    'status' => 'added',
                    'additions' => 45,
                    'deletions' => 0,
                    'changes' => 45,
                    'language' => 'php',
                ],
                [
                    'filename' => 'tests/PaymentProcessorTest.php',
                    'status' => 'added',
                    'additions' => 30,
                    'deletions' => 0,
                    'changes' => 30,
                    'language' => 'php',
                ],
                [
                    'filename' => 'config/payment.php',
                    'status' => 'modified',
                    'additions' => 5,
                    'deletions' => 2,
                    'changes' => 7,
                    'language' => 'php',
                ],
            ];

            foreach ($files as $fileData) {
                \App\Models\PullRequestFile::create([
                    'pull_request_id' => $pr->id,
                    ...$fileData
                ]);
            }

            return response()->json([
                'message' => 'Sample PR created',
                'pull_request' => $pr->load('files')
            ]);
        });

        // Test 6: Trigger PR review
        Route::post('trigger-pr-review/{pr}', function (\App\Models\PullRequest $pr) {
            if ($pr->repository->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            \App\Jobs\ProcessPullRequestReview::dispatch($pr);

            return response()->json([
                'message' => 'PR review job dispatched',
                'pr_id' => $pr->id
            ]);
        });

        // Test 7: Queue status
        Route::get('queue-status', function () {
            // Get pending jobs count (this depends on your queue driver)
            $stats = [
                'timestamp' => now(),
                'queue_connection' => config('queue.default'),
                'note' => 'Check your queue worker is running: php artisan queue:work'
            ];

            return response()->json($stats);
        });

        // Test 8: Clear all test data
        Route::delete('cleanup', function () {
            $user = auth()->user();

            // Delete test reviews
            \App\Models\Review::whereHas('codeSubmission', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->delete();

            \App\Models\PullRequestReview::whereHas('pullRequest.repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->delete();

            // Delete test PRs and files
            \App\Models\PullRequestFile::whereHas('pullRequest.repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->delete();

            \App\Models\PullRequest::whereHas('repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->delete();

            // Delete test submissions
            $user->codeSubmissions()->delete();

            return response()->json(['message' => 'Test data cleaned up']);
        });
    });
