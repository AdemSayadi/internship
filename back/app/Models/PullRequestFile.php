<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PullRequestFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'status', // added, modified, removed, renamed
        'additions',
        'deletions',
        'changes',
        'blob_url',
        'raw_url',
        'patch',
        'previous_filename', // for renamed files
        'language',
        'pull_request_id',
    ];

    protected $casts = [
        'additions' => 'integer',
        'deletions' => 'integer',
        'changes' => 'integer',
    ];

    public function pullRequest(): BelongsTo
    {
        return $this->belongsTo(PullRequest::class);
    }

    // Helper methods
    public function isAdded(): bool
    {
        return $this->status === 'added';
    }

    public function isModified(): bool
    {
        return $this->status === 'modified';
    }

    public function isRemoved(): bool
    {
        return $this->status === 'removed';
    }

    public function isRenamed(): bool
    {
        return $this->status === 'renamed';
    }
}
