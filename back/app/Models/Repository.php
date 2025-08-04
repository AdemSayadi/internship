<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_private'
    ];

    protected $casts = [
        'is_private' => 'boolean'
    ];

    // Add this relationship if you don't have it
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to CodeSubmissions
    public function codeSubmissions()
    {
        return $this->hasMany(CodeSubmission::class);
    }
}
