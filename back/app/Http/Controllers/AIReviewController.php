<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCodeSubmissionReview;
use App\Jobs\ProcessPullRequestReview;
use App\Models\CodeSubmission;
use App\Models\PullRequest;
use App\Models\Review;
use App\Models\PullRequestReview;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AIReviewController extends Controller
{
    /**
     * Trigger AI review for code submission
     */
    public function reviewCodeSubmission(Request $request, CodeSubmission $codeSubmission): JsonResponse
    {
        // Check if user owns the code submission or repository
        if ($codeSubmission->user_id !== Auth::id() &&
            $codeSubmission->repository?->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if there's already a pending or completed review
        $existingReview = $codeSubmission->reviews()
            ->whereIn('status', ['processing', 'completed'])
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Review already exists',
                'review' => $existingReview
            ]);
        }

        // Dispatch the review job
        ProcessCodeSubmissionReview::dispatch($codeSubmission);

        return response()->json([
            'message' => 'AI review has been queued and will be processed shortly',
            'submission_id' => $codeSubmission->id
        ]);
    }

    /**
     * Get review status and results for code submission
     */
    public function getCodeSubmissionReview(CodeSubmission $codeSubmission): JsonResponse
    {
        // Check authorization
        if ($codeSubmission->user_id !== Auth::id() &&
            $codeSubmission->repository?->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = $codeSubmission->reviews()->latest()->first();

        if (!$review) {
            return response()->json(['message' => 'No review found'], 404);
        }

        return response()->json([
            'review' => $review,
            'code_submission' => $codeSubmission->load('repository', 'user')
        ]);
    }

    /**
     * Trigger AI review for pull request
     */
    public function reviewPullRequest(Request $request, PullRequest $pullRequest): JsonResponse
    {
        // Check if user owns the repository
        if ($pullRequest->repository->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if there's already a pending or completed AI review
        $existingReview = $pullRequest->reviews()
            ->where('review_type', 'ai_auto')
            ->whereIn('status', ['pending', 'completed'])
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'AI review already exists',
                'review' => $existingReview
            ]);
        }

        // Dispatch the review job
        ProcessPullRequestReview::dispatch($pullRequest);

        return response()->json([
            'message' => 'AI review has been queued and will be processed shortly',
            'pull_request_id' => $pullRequest->id
        ]);
    }

    /**
     * Get review status and results for pull request
     */
    public function getPullRequestReview(PullRequest $pullRequest): JsonResponse
    {
        // Check authorization
        if ($pullRequest->repository->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reviews = $pullRequest->reviews()
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'reviews' => $reviews,
            'pull_request' => $pullRequest->load('repository', 'files')
        ]);
    }

    /**
     * Get AI review for a specific pull request (AI reviews only)
     */
    public function getAIReview(PullRequest $pullRequest): JsonResponse
    {
        // Check authorization
        if ($pullRequest->repository->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $aiReview = $pullRequest->reviews()
            ->where('review_type', 'ai_auto')
            ->latest()
            ->first();

        if (!$aiReview) {
            return response()->json(['message' => 'No AI review found'], 404);
        }

        return response()->json([
            'review' => $aiReview,
            'pull_request' => $pullRequest->load('repository', 'files')
        ]);
    }

    /**
     * Auto-trigger review on webhook (called from webhook controller)
     */
    public function autoReviewPullRequest(PullRequest $pullRequest): void
    {
        // Only auto-review if the repository has auto-review enabled
        // You can add this flag to your Repository model
        if ($pullRequest->repository->webhook_enabled) {
            ProcessPullRequestReview::dispatch($pullRequest);
        }
    }

    /**
     * Batch review multiple code submissions
     */
    public function batchReviewCodeSubmissions(Request $request): JsonResponse
    {
        $request->validate([
            'submission_ids' => 'required|array|max:10',
            'submission_ids.*' => 'exists:code_submissions,id'
        ]);

        $submissionIds = $request->submission_ids;
        $codeSubmissions = CodeSubmission::whereIn('id', $submissionIds)
            ->where(function($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('repository', function($q) {
                        $q->where('user_id', Auth::id());
                    });
            })
            ->get();

        $queued = [];
        $skipped = [];

        foreach ($codeSubmissions as $submission) {
            $existingReview = $submission->reviews()
                ->whereIn('status', ['processing', 'completed'])
                ->exists();

            if ($existingReview) {
                $skipped[] = $submission->id;
                continue;
            }

            ProcessCodeSubmissionReview::dispatch($submission);
            $queued[] = $submission->id;
        }

        return response()->json([
            'message' => 'Batch review processing started',
            'queued' => $queued,
            'skipped' => $skipped,
            'total_queued' => count($queued)
        ]);
    }

    /**
     * Get review statistics for user's submissions
     */
    public function getReviewStats(): JsonResponse
    {
        $userId = Auth::id();

        $stats = [
            'total_submissions' => CodeSubmission::where('user_id', $userId)->count(),
            'reviewed_submissions' => CodeSubmission::where('user_id', $userId)
                ->whereHas('reviews', function($q) {
                    $q->where('status', 'completed');
                })->count(),
            'pending_reviews' => CodeSubmission::where('user_id', $userId)
                ->whereHas('reviews', function($q) {
                    $q->where('status', 'processing');
                })->count(),
            'failed_reviews' => CodeSubmission::where('user_id', $userId)
                ->whereHas('reviews', function($q) {
                    $q->where('status', 'failed');
                })->count(),
            'average_score' => Review::whereHas('codeSubmission', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
                ->where('status', 'completed')
                ->avg('overall_score'),
            'total_pull_requests' => PullRequest::whereHas('repository', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->count(),
            'ai_reviewed_prs' => PullRequestReview::whereHas('pullRequest.repository', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
                ->where('review_type', 'ai_auto')
                ->where('status', 'completed')
                ->count(),
        ];

        return response()->json(['stats' => $stats]);
    }
}
