<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Services\UserLicense;
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
        'license_key',
        'license_issued_at',
        'license_revoked_at',
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
            'license_issued_at' => 'datetime',
            'license_revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (filled($user->license_key)) {
                $user->license_key = UserLicense::normalize($user->license_key);
                $user->license_issued_at ??= now();

                return;
            }

            $user->license_key = UserLicense::uniqueKey();
            $user->license_issued_at = now();
        });
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

    public function hasValidLicense(): bool
    {
        return filled($this->license_key) && $this->license_revoked_at === null;
    }

    public function issueLicense(?string $key = null): void
    {
        $this->forceFill([
            'license_key' => $key ? UserLicense::normalize($key) : UserLicense::uniqueKey(),
            'license_issued_at' => now(),
            'license_revoked_at' => null,
        ])->save();
    }

    public function revokeLicense(): void
    {
        $this->forceFill([
            'license_revoked_at' => now(),
        ])->save();
    }

    public function restoreLicense(): void
    {
        $this->forceFill([
            'license_revoked_at' => null,
        ])->save();
    }
}
