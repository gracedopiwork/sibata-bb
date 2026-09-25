<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'nip',
        'telegram_id',
        'role',
        'is_active',
        'password',
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'telegram_id' => 'integer',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EvidenceLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canAccessDashboard(): bool
    {
        return $this->role->canAccessDashboard();
    }

    public function canManageEvidence(): bool
    {
        return $this->role->canManageEvidence();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }
}
