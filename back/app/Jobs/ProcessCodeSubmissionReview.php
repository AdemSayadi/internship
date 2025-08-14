<?php

namespace App\Jobs;

use App\Models\CodeSubmission;
use App\Models\Review;
use App\Services\AICodeReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Http;

class ProcessCodeSubmissionReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected CodeSubmission $codeSubmission;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times on failure
    public $backoff = [60, 300, 600]; // Retry after 1, 5, and 10 minutes

    public function __construct(CodeSubmission $codeSubmission)
    {
        $this->codeSubmission = $codeSubmission;
    }

    public function handle(AICodeReviewService $reviewService): void
    {
        try {
            Log::info('Starting AI review for code submission', [
                'submission_id' => $this->codeSubmission->id
            ]);

            // Create processing review record
            $review = $this->codeSubmission->reviews()->create([
                'status' => Review::STATUS_PROCESSING
            ]);

            // Check API status first
            if (!$this->isApiAvailable()) {
                throw new Exception('AI API service is currently unavailable');
            }

            // Process the review
            $review = $reviewService->reviewCodeSubmission($this->codeSubmission, $review);

            // Update to completed
            $review->update([
                'status' => Review::STATUS_COMPLETED,
                'processing_time' => microtime(true) - LARAVEL_START
            ]);

            Log::info('AI review completed for code submission', [
                'submission_id' => $this->codeSubmission->id,
                'review_id' => $review->id,
                'score' => $review->overall_score
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process code submission review', [
                'submission_id' => $this->codeSubmission->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);

            // Mark as failed if review exists
            if (isset($review)) {
                $review->update([
                    'status' => Review::STATUS_FAILED,
                    'ai_summary' => 'Review failed: ' . $e->getMessage()
                ]);
            }

            // Only throw if we've exhausted all retries
            if ($this->attempts() >= $this->tries) {
                $this->fail($e);
            } else {
                // Calculate delay for next attempt
                $delay = $this->backoff[$this->attempts() - 1] ?? 60;
                $this->release($delay);
            }
        }
    }

    protected function isApiAvailable(): bool
    {
        try {
            $response = Http::get('https://groqstatus.com');
            return $response->successful() && !str_contains($response->body(), 'active incident');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Code submission review permanently failed', [
            'submission_id' => $this->codeSubmission->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark any pending review as failed
        $this->codeSubmission->reviews()
            ->whereIn('status', [Review::STATUS_PROCESSING])
            ->update([
                'status' => Review::STATUS_FAILED,
                'ai_summary' => 'Permanently failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
            ]);
    }
}
