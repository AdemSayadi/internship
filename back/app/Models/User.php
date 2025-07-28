<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
use HasFactory, Notifiable, HasApiTokens;

protected $fillable = [
'name',
'email',
'password',
'github_id',
];

protected $hidden = [
'password',
'remember_token',
];

protected function casts(): array
{
return [
'email_verified_at' => 'datetime',
'password' => 'hashed',
];
}

public function repositories(): HasMany
{
return $this->hasMany(Repository::class);
}

public function codeSubmissions(): HasMany
{
return $this->hasMany(CodeSubmission::class);
}

public function notifications(): HasMany
{
return $this->hasMany(Notification::class);
}
}
