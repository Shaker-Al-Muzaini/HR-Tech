<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'preferred_language',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // ─── Relationships ───

    public function interviews()
    {
        return $this->hasMany(Interview::class, 'hr_user_id');
    }

    // ─── Helpers ───

    public function isHR(): bool     { return $this->role === 'hr'; }
    public function isManager(): bool { return $this->role === 'manager'; }
    public function isAdmin(): bool   { return $this->role === 'admin'; }

    public function canManageInterviews(): bool
    {
        return in_array($this->role, ['hr', 'admin']);
    }
}
