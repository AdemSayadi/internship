<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'review_id',
        'type',
        'title',
        'message',
        'data',
        'read',
    ];

    protected $casts = [
        'read' => 'boolean',
        'data' => 'array',
    ];

    // Notification types
    const TYPE_CODE_SUBMISSION_CREATED = 'code_submission_created';
    const TYPE_REVIEW_SUBMITTED = 'review_submitted'; // Added missing constant
    const TYPE_REVIEW_COMPLETED = 'review_completed';
    const TYPE_PULL_REQUEST_CREATED = 'pull_request_created';
    const TYPE_PR_REVIEW_COMPLETED = 'pr_review_completed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    // Scope for unread notifications
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    // Scope for read notifications
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    // Helper method to mark as read
    public function markAsRead()
    {
        $this->update(['read' => true]);
    }

    // Helper method to get all notification types
    public static function getTypes(): array
    {
        return [
            self::TYPE_CODE_SUBMISSION_CREATED,
            self::TYPE_REVIEW_SUBMITTED,
            self::TYPE_REVIEW_COMPLETED,
            self::TYPE_PULL_REQUEST_CREATED,
            self::TYPE_PR_REVIEW_COMPLETED,
        ];
    }
}
