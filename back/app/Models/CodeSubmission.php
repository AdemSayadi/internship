<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Events\CodeSubmissionCreated;

class CodeSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'language',
        'code_content',
        'file_path',
        'repository_id',
        'user_id',
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
        return $this->hasMany(Review::class);
    }
    protected static function booted()
    {
        static::created(function ($codeSubmission) {
            // Dispatch event instead of creating notification directly
            CodeSubmissionCreated::dispatch($codeSubmission, $codeSubmission->user);
        });
    }
}

