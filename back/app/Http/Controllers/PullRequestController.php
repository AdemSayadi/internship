<?php

namespace App\Http\Controllers;

use App\Models\PullRequest;
use App\Models\Repository;
use App\Services\PullRequestReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PullRequestController extends Controller
{
    protected PullRequestReviewService $reviewService;

    public function __construct(PullRequestReviewService $reviewService)
    {
        $this->middleware('auth:sanctum');
        $this->reviewService = $reviewService;
    }

    /**
     * Display a listing of pull requests for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $query = PullRequest::whereHas('repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with(['repository', 'reviews']);

            // Filter by repository if provided
            if ($request->has('repository_id')) {
                $query->where('repository_id', $request->repository_id);
            }

            // Filter by state if provided
            if ($request->has('state')) {
                $query->where('state', $request->state);
            }

            $pullRequests = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'pull_requests' => $pullRequests
            ]);

        } catch (\Exception $e) {
            Log::error('Get pull requests error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pull requests'
            ], 500);
        }
    }

    /**
     * Display the specified pull request
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $pullRequest = PullRequest::with(['repository', 'reviews', 'files'])
                ->whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->where('id', $id)
                ->first();

            if (!$pullRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pull request not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'pull_request' => $pullRequest
            ]);

        } catch (\Exception $e) {
            Log::error('Get pull request error', [
                'message' => $e->getMessage(),
                'pr_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pull request'
            ], 500);
        }
    }

    /**
     * Get reviews for a specific pull request
     */
    public function reviews(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $pullRequest = PullRequest::whereHas('repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('id', $id)->first();

            if (!$pullRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pull request not found or unauthorized'
                ], 404);
            }

            $reviews = $pullRequest->reviews()
                ->with('reviewer')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'reviews' => $reviews
            ]);

        } catch (\Exception $e) {
            Log::error('Get pull request reviews error', [
                'message' => $e->getMessage(),
                'pr_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews'
            ], 500);
        }
    }

    /**
     * Create a manual review for a pull request
     */
    public function createReview(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $pullRequest = PullRequest::whereHas('repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('id', $id)->first();

            if (!$pullRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pull request not found or unauthorized'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'summary' => 'required|string',
                'score' => 'nullable|integer|min:1|max:10',
                'feedback' => 'nullable|string',
                'suggestions' => 'nullable|array',
                'security_issues' => 'nullable|array',
                'performance_issues' => 'nullable|array',
                'code_quality_issues' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review = $this->reviewService->createManualReview(
                $pullRequest,
                $user->id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Review created successfully',
                'review' => $review->load('reviewer')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create pull request review error', [
                'message' => $e->getMessage(),
                'pr_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create review'
            ], 500);
        }
    }

    /**
     * Trigger a new automatic review
     */
    public function triggerReview(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $pullRequest = PullRequest::whereHas('repository', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('id', $id)->first();

            if (!$pullRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pull request not found or unauthorized'
                ], 404);
            }

            // Check if PR is still open
            if (!$pullRequest->isOpen()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot review closed or merged pull requests'
                ], 400);
            }

            $this->reviewService->triggerAutomaticReview($pullRequest);

            return response()->json([
                'success' => true,
                'message' => 'Review triggered successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Trigger pull request review error', [
                'message' => $e->getMessage(),
                'pr_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger review'
            ], 500);
        }
    }

    /**
     * Get pull requests for a specific repository
     */
    public function getByRepository(string $repositoryId): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $repository = Repository::where('id', $repositoryId)
                ->where('user_id', $user->id)
                ->first();

            if (!$repository) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repository not found or unauthorized'
                ], 404);
            }

            $pullRequests = $repository->pullRequests()
                ->with(['reviews'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'pull_requests' => $pullRequests
            ]);

        } catch (\Exception $e) {
            Log::error('Get repository pull requests error', [
                'message' => $e->getMessage(),
                'repository_id' => $repositoryId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pull requests'
            ], 500);
        }
    }

    /**
     * Get statistics for pull requests
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $stats = [
                'total' => PullRequest::whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->count(),
                'open' => PullRequest::whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('state', 'open')->count(),
                'merged' => PullRequest::whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('state', 'merged')->count(),
                'closed' => PullRequest::whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('state', 'closed')->count(),
                'reviewed' => PullRequest::whereHas('repository', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->whereHas('reviews')->count(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get pull request statistics error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }
}
