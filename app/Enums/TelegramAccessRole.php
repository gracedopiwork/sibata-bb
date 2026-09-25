<?php

namespace App\Enums;

enum TelegramAccessRole: string
{
    case AdminPb3r = 'ADMIN_PB3R';
    case JpuPegawai = 'JPU_PEGAWAI';

    public function label(): string
    {
        return match ($this) {
            self::AdminPb3r => 'Admin PB3R',
            self::JpuPegawai => 'JPU / Pegawai',
        };
    }

    public function canMutateWarehouse(): bool
    {
        return $this === self::AdminPb3r;
    }
}
