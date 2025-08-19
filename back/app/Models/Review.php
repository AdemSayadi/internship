<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Events\ReviewSubmitted;
use App\Events\ReviewCompleted;

class Review extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'code_submission_id',
        'overall_score',
        'complexity_score',
        'security_score',
        'maintainability_score',
        'bug_count',
        'ai_summary',
        'suggestions',
        'status',
        'processing_time',
    ];

    protected $casts = [
        'suggestions' => 'array',
        'processing_time' => 'float',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    public function codeSubmission(): BelongsTo
    {
        return $this->belongsTo(CodeSubmission::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    protected static function booted()
    {
        static::created(function ($review) {
            // Dispatch ReviewSubmitted event when review is created
            ReviewSubmitted::dispatch($review, $review->codeSubmission->user);
        });

        static::updated(function ($review) {
            // Dispatch ReviewCompleted event when status changes to completed
            if ($review->isDirty('status') && $review->status === self::STATUS_COMPLETED) {
                ReviewCompleted::dispatch($review, $review->codeSubmission->user);
            }
        });
    }
}
