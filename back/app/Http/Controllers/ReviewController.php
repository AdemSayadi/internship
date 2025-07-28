<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\CodeSubmission;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Review::whereHas('codeSubmission', function ($q) {
                $q->where('user_id', auth()->id());
            })->with(['codeSubmission.repository']);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $reviews = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'reviews' => $reviews
            ]);

        } catch (\Exception $e) {
            Log::error('Get reviews error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews'
            ], 500);
        }
    }

    /**
     * Create a new AI-powered code review
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code_submission_id' => 'required|exists:code_submissions,id',
            ]);

            // Verify the code submission belongs to the authenticated user
            $codeSubmission = CodeSubmission::where('id', $request->code_submission_id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$codeSubmission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code submission not found or unauthorized'
                ], 404);
            }

            // Check if there's already a pending review
            $existingReview = Review::where('code_submission_id', $request->code_submission_id)
                ->where('status', 'pending')
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'A review is already in progress for this code submission'
                ], 409);
            }

            // Create review record with pending status
            $review = Review::create([
                'code_submission_id' => $request->code_submission_id,
                'status' => 'pending',
                'overall_score' => 0,
                'complexity_score' => 0,
                'security_score' => 0,
                'maintainability_score' => 0,
                'bug_count' => 0,
            ]);

            Log::info('Review created', [
                'review_id' => $review->id,
                'submission_id' => $request->code_submission_id,
                'user_id' => auth()->id()
            ]);

            // Process the review asynchronously
            $this->processAIReview($review, $codeSubmission);

            return response()->json([
                'success' => true,
                'message' => 'Code review initiated successfully',
                'review' => $review->load('codeSubmission')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create review error', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create review'
            ], 500);
        }
    }

    /**
     * Display the specified review
     */
    public function show(string $id): JsonResponse
    {
        try {
            $review = Review::with(['codeSubmission.repository', 'codeSubmission.user'])
                ->whereHas('codeSubmission', function ($q) {
                    $q->where('user_id', auth()->id());
                })
                ->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'review' => $review
            ]);

        } catch (\Exception $e) {
            Log::error('Get review error', [
                'message' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve review'
            ], 500);
        }
    }

    /**
     * Update the specified review (mainly for manual adjustments)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $review = Review::whereHas('codeSubmission', function ($q) {
                $q->where('user_id', auth()->id());
            })->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found or unauthorized'
                ], 404);
            }

            $request->validate([
                'ai_summary' => 'sometimes|string',
                'suggestions' => 'sometimes|array',
                'status' => 'sometimes|in:pending,completed,failed',
            ]);

            $review->update($request->only(['ai_summary', 'suggestions', 'status']));

            Log::info('Review updated', [
                'review_id' => $review->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'review' => $review->fresh(['codeSubmission'])
            ]);

        } catch (\Exception $e) {
            Log::error('Update review error', [
                'message' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    /**
     * Remove the specified review
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $review = Review::whereHas('codeSubmission', function ($q) {
                $q->where('user_id', auth()->id());
            })->find($id);

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found or unauthorized'
                ], 404);
            }

            $review->delete();

            Log::info('Review deleted', [
                'review_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete review error', [
                'message' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

    /**
     * Process AI review (mock implementation - replace with actual AI service)
     */
    private function processAIReview(Review $review, CodeSubmission $codeSubmission): void
    {
        try {
            $startTime = microtime(true);

            // Mock AI analysis - Replace this with actual AI service integration
            $aiAnalysis = $this->mockAIAnalysis($codeSubmission);

            $processingTime = microtime(true) - $startTime;

            // Update the review with AI results
            $review->update([
                'overall_score' => $aiAnalysis['overall_score'],
                'complexity_score' => $aiAnalysis['complexity_score'],
                'security_score' => $aiAnalysis['security_score'],
                'maintainability_score' => $aiAnalysis['maintainability_score'],
                'bug_count' => $aiAnalysis['bug_count'],
                'ai_summary' => $aiAnalysis['summary'],
                'suggestions' => $aiAnalysis['suggestions'],
                'status' => 'completed',
                'processing_time' => $processingTime,
            ]);

            // Create notification for the user
            Notification::create([
                'user_id' => $codeSubmission->user_id,
                'review_id' => $review->id,
                'message' => "Code review completed for '{$codeSubmission->title}'",
                'read' => false,
            ]);

            Log::info('AI review completed', [
                'review_id' => $review->id,
                'processing_time' => $processingTime,
                'overall_score' => $aiAnalysis['overall_score']
            ]);

        } catch (\Exception $e) {
            Log::error('AI review processing failed', [
                'review_id' => $review->id,
                'message' => $e->getMessage()
            ]);

            $review->update([
                'status' => 'failed',
                'ai_summary' => 'Review processing failed. Please try again.',
            ]);
        }
    }

    /**
     * Mock AI analysis - Replace with actual AI service
     */
    private function mockAIAnalysis(CodeSubmission $codeSubmission): array
    {
        // This is a mock implementation
        // Replace with actual AI service integration (OpenAI, Claude, etc.)

        $codeLength = strlen($codeSubmission->code_content);
        $language = strtolower($codeSubmission->language);

        // Mock scoring based on code characteristics
        $complexityScore = min(100, max(0, 100 - ($codeLength / 50)));
        $securityScore = rand(70, 95);
        $maintainabilityScore = rand(65, 90);
        $bugCount = rand(0, 5);
        $overallScore = round(($complexityScore + $securityScore + $maintainabilityScore) / 3);

        return [
            'overall_score' => $overallScore,
            'complexity_score' => round($complexityScore),
            'security_score' => $securityScore,
            'maintainability_score' => $maintainabilityScore,
            'bug_count' => $bugCount,
            'summary' => "Code analysis completed for {$language} code. The code shows " .
                ($overallScore >= 80 ? "good" : ($overallScore >= 60 ? "moderate" : "poor")) .
                " quality with {$bugCount} potential issues identified.",
            'suggestions' => [
                'Consider adding more comments for better code documentation',
                'Review variable naming conventions for consistency',
                'Consider breaking down complex functions into smaller ones',
                'Add error handling for edge cases',
                'Consider adding unit tests for better code coverage'
            ]
        ];
    }

    /**
     * Get review statistics for the authenticated user
     */
    public function statistics(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $stats = [
                'total_reviews' => Review::whereHas('codeSubmission', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->count(),

                'completed_reviews' => Review::whereHas('codeSubmission', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('status', 'completed')->count(),

                'pending_reviews' => Review::whereHas('codeSubmission', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('status', 'pending')->count(),

                'average_score' => Review::whereHas('codeSubmission', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('status', 'completed')->avg('overall_score'),

                'total_bugs_found' => Review::whereHas('codeSubmission', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->where('status', 'completed')->sum('bug_count'),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get review statistics error', [
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
