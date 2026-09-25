<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case PetugasPb3r = 'petugas_pb3r';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::PetugasPb3r => 'Petugas PB3R (Telegram)',
        };
    }

    public function canAccessDashboard(): bool
    {
        return $this === self::Admin;
    }

    public function canManageEvidence(): bool
    {
        return in_array($this, [self::Admin, self::PetugasPb3r], true);
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }
}
