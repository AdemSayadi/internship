<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'provider',
        'user_id',
        'github_repo_id',
        'full_name',
        'is_private',
        'webhook_id',
        'webhook_enabled',
        'webhook_created_at'
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'webhook_enabled' => 'boolean',
        'webhook_created_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function codeSubmissions(): HasMany
    {
        return $this->hasMany(CodeSubmission::class);
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class);
    }

    // Scopes
    public function scopeGithub($query)
    {
        return $query->where('provider', 'github');
    }

    public function scopeWithWebhook($query)
    {
        return $query->where('webhook_enabled', true);
    }

    // Helper methods
    public function isGithubRepo(): bool
    {
        return $this->provider === 'github';
    }

    public function hasWebhook(): bool
    {
        return $this->webhook_enabled && !empty($this->webhook_id);
    }

    public function getWebhookUrl(): string
    {
        return url('/api/webhooks/github');
    }
}
