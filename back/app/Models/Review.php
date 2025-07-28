<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

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

    public function codeSubmission(): BelongsTo
    {
        return $this->belongsTo(CodeSubmission::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
