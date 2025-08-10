<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\CodeSubmissionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\GitHubWebhookController;
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
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', function () {
            $user = auth()->user();
            return response()->json([
                'success' => true,
                'stats' => [
                    'repositories' => $user->repositories()->count(),
                    'submissions' => $user->codeSubmissions()->count(),
                    'pull_requests' => \App\Models\PullRequest::whereHas('repository', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->count(),
                    'reviews' => $user->codeSubmissions()->withCount('reviews')->get()->sum('reviews_count'),
                    'notifications' => $user->notifications()->where('read', false)->count(),
                ]
            ]);
        });
    });
});
