<?php

namespace App\Services;

use App\Models\PullRequest;
use App\Models\PullRequestReview;
use App\Jobs\ProcessPullRequestReview;
use Illuminate\Support\Facades\Log;

class PullRequestReviewService
{
    public function triggerAutomaticReview(PullRequest $pullRequest): void
    {
        try {
            // Dispatch job to process the review asynchronously
            ProcessPullRequestReview::dispatch($pullRequest);

            Log::info('Automatic review triggered', [
                'pull_request_id' => $pullRequest->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger automatic review', [
                'pull_request_id' => $pullRequest->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function processReview(PullRequest $pullRequest, PullRequestReview $review): void
    {
        try {
            $review->update(['status' => 'processing']);

            // Get all files from the pull request
            $files = $pullRequest->files()->get();

            if ($files->isEmpty()) {
                Log::warning('No files found for PR review', [
                    'pull_request_id' => $pullRequest->id
                ]);

                $review->update([
                    'status' => 'completed',
                    'summary' => 'No files to review',
                    'reviewed_at' => now(),
                ]);
                return;
            }

            // For now, create a basic review
            // TODO: Integrate with AI service for actual code analysis
            $this->performBasicReview($pullRequest, $review, $files);

        } catch (\Exception $e) {
            Log::error('Failed to process PR review', [
                'pull_request_id' => $pullRequest->id,
                'review_id' => $review->id,
                'error' => $e->getMessage()
            ]);

            $review->update([
                'status' => 'failed',
                'summary' => 'Review processing failed: ' . $e->getMessage(),
            ]);
        }
    }

    protected function performBasicReview(PullRequest $pullRequest, PullRequestReview $review, $files): void
    {
        $totalAdditions = $files->sum('additions');
        $totalDeletions = $files->sum('deletions');
        $totalChanges = $files->sum('changes');

        $suggestions = [];
        $securityIssues = [];
        $performanceIssues = [];
        $qualityIssues = [];

        // Basic analysis
        foreach ($files as $file) {
            // Check for large files
            if ($file->changes > 500) {
                $qualityIssues[] = [
                    'file' => $file->filename,
                    'issue' => 'Large file change',
                    'description' => "File has {$file->changes} changes. Consider breaking this into smaller commits.",
                    'severity' => 'medium'
                ];
            }

            // Check for potentially sensitive files
            if (str_contains($file->filename, '.env') ||
                str_contains($file->filename, 'config') ||
                str_contains($file->filename, 'secret')) {
                $securityIssues[] = [
                    'file' => $file->filename,
                    'issue' => 'Potentially sensitive file',
                    'description' => 'This file may contain sensitive configuration. Ensure no secrets are committed.',
                    'severity' => 'high'
                ];
            }

            // Language-specific checks
            if ($file->language === 'php') {
                $suggestions[] = [
                    'file' => $file->filename,
                    'suggestion' => 'PHP Code Review',
                    'description' => 'Consider running PHPStan or Psalm for static analysis of this PHP file.',
                    'type' => 'tool_recommendation'
                ];
            } elseif (in_array($file->language, ['javascript', 'typescript'])) {
                $suggestions[] = [
                    'file' => $file->filename,
                    'suggestion' => 'JavaScript/TypeScript Review',
                    'description' => 'Consider running ESLint and ensuring proper type annotations.',
                    'type' => 'tool_recommendation'
                ];
            }
        }

        // Calculate basic score
        $score = $this->calculateReviewScore($totalChanges, count($qualityIssues), count($securityIssues));

        // Generate summary
        $summary = $this->generateReviewSummary($pullRequest, $totalAdditions, $totalDeletions, $files->count());

        // Update review
        $review->update([
            'status' => 'completed',
            'summary' => $summary,
            'score' => $score,
            'feedback' => $this->generateDetailedFeedback($pullRequest, $files),
            'suggestions' => $suggestions,
            'security_issues' => $securityIssues,
            'performance_issues' => $performanceIssues,
            'code_quality_issues' => $qualityIssues,
            'reviewed_at' => now(),
        ]);

        Log::info('PR review completed', [
            'pull_request_id' => $pullRequest->id,
            'review_id' => $review->id,
            'score' => $score
        ]);
    }

    protected function calculateReviewScore(int $totalChanges, int $qualityIssues, int $securityIssues): int
    {
        $score = 8; // Start with a good score

        // Deduct points for issues
        $score -= min($securityIssues * 2, 4); // Security issues are serious
        $score -= min($qualityIssues, 3); // Quality issues

        // Adjust for size
        if ($totalChanges > 1000) {
            $score -= 1; // Large changes are harder to review
        }

        return max(1, min(10, $score)); // Keep between 1-10
    }

    protected function generateReviewSummary(PullRequest $pullRequest, int $additions, int $deletions, int $fileCount): string
    {
        $summary = "Automated review for PR #{$pullRequest->github_pr_number}: {$pullRequest->title}\n\n";
        $summary .= "📊 **Changes Overview:**\n";
        $summary .= "- {$fileCount} files modified\n";
        $summary .= "- {$additions} lines added\n";
        $summary .= "- {$deletions} lines deleted\n\n";

        if ($additions + $deletions > 500) {
            $summary .= "⚠️ This is a large PR. Consider breaking it into smaller, focused changes.\n\n";
        }

        $summary .= "This is an automated review. Manual review is still recommended for complex changes.";

        return $summary;
    }

    protected function generateDetailedFeedback(PullRequest $pullRequest, $files): string
    {
        $feedback = "## Detailed Analysis\n\n";

        $languageCount = $files->groupBy('language')->map->count();

        $feedback .= "### Languages Modified:\n";
        foreach ($languageCount as $language => $count) {
            $feedback .= "- " . ucfirst($language ?: 'Unknown') . ": {$count} files\n";
        }

        $feedback .= "\n### File Changes:\n";
        foreach ($files as $file) {
            $feedback .= "**{$file->filename}** ({$file->status})\n";
            $feedback .= "- +{$file->additions} -{$file->deletions} lines\n";

            if ($file->status === 'renamed' && $file->previous_filename) {
                $feedback .= "- Renamed from: {$file->previous_filename}\n";
            }

            $feedback .= "\n";
        }

        return $feedback;
    }

    public function createManualReview(PullRequest $pullRequest, int $userId, array $reviewData): PullRequestReview
    {
        return PullRequestReview::create([
            'review_type' => 'manual',
            'status' => 'completed',
            'summary' => $reviewData['summary'],
            'score' => $reviewData['score'] ?? null,
            'feedback' => $reviewData['feedback'] ?? null,
            'suggestions' => $reviewData['suggestions'] ?? [],
            'security_issues' => $reviewData['security_issues'] ?? [],
            'performance_issues' => $reviewData['performance_issues'] ?? [],
            'code_quality_issues' => $reviewData['code_quality_issues'] ?? [],
            'pull_request_id' => $pullRequest->id,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);
    }
}
