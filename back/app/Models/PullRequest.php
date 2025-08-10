<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PullRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'github_pr_id',
        'github_pr_number',
        'state', // open, closed, merged
        'html_url',
        'head_sha',
        'base_sha',
        'head_branch',
        'base_branch',
        'author_username',
        'author_avatar_url',
        'mergeable',
        'merged_at',
        'closed_at',
        'repository_id',
        'user_id',
        'webhook_data', // JSON field for storing raw webhook data
    ];

    protected $casts = [
        'mergeable' => 'boolean',
        'merged_at' => 'datetime',
        'closed_at' => 'datetime',
        'webhook_data' => 'array',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PullRequestReview::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(PullRequestFile::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('state', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('state', 'closed');
    }

    public function scopeMerged($query)
    {
        return $query->where('state', 'merged');
    }

    // Helper methods
    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    public function isClosed(): bool
    {
        return $this->state === 'closed';
    }

    public function isMerged(): bool
    {
        return $this->state === 'merged';
    }
}
