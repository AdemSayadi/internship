<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\PullRequestReviewCompleted;

class PullRequestReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_type', // ai_auto, manual
        'status', // pending, completed, failed
        'summary',
        'score', // overall score 1-10
        'feedback', // detailed feedback
        'suggestions', // JSON array of suggestions
        'security_issues', // JSON array of security concerns
        'performance_issues', // JSON array of performance concerns
        'code_quality_issues', // JSON array of quality issues
        'pull_request_id',
        'reviewed_by', // user_id if manual, 'ai' if automated
        'reviewed_at',
    ];

    protected $casts = [
        'suggestions' => 'array',
        'security_issues' => 'array',
        'performance_issues' => 'array',
        'code_quality_issues' => 'array',
        'reviewed_at' => 'datetime',
        'score' => 'integer',
    ];

    public function pullRequest(): BelongsTo
    {
        return $this->belongsTo(PullRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAiReviews($query)
    {
        return $query->where('review_type', 'ai_auto');
    }

    public function scopeManualReviews($query)
    {
        return $query->where('review_type', 'manual');
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAiReview(): bool
    {
        return $this->review_type === 'ai_auto';
    }

    public function isManualReview(): bool
    {
        return $this->review_type === 'manual';
    }
    protected static function booted()
    {
        static::updated(function ($pullRequestReview) {
            // Only dispatch when status changes to completed
            if ($pullRequestReview->isDirty('status') && $pullRequestReview->status === 'completed') {
                PullRequestReviewCompleted::dispatch($pullRequestReview, $pullRequestReview->pullRequest->user);
            }
        });
    }
}
