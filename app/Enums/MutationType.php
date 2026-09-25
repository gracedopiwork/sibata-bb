<?php

namespace App\Enums;

enum MutationType: string
{
    case CheckIn = 'CHECK_IN';
    case PinjamSidang = 'PINJAM_SIDANG';
    case KembaliGudang = 'KEMBALI_GUDANG';
    case Eksekusi = 'EKSEKUSI';

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Check-in gudang',
            self::PinjamSidang => 'Pinjam sidang',
            self::KembaliGudang => 'Kembali gudang',
            self::Eksekusi => 'Eksekusi',
        };
    }
}
