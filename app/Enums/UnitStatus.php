<?php

namespace App\Enums;

enum UnitStatus: string
{
    case TersimpanGudang = 'TERSIMPAN_GUDANG';
    case DipinjamSidang = 'DIPINJAM_SIDANG';
    case Selesai = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::TersimpanGudang => 'Tersimpan gudang',
            self::DipinjamSidang => 'Dipinjam sidang',
            self::Selesai => 'Selesai',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TersimpanGudang => 'badge-success',
            self::DipinjamSidang => 'badge-warning',
            self::Selesai => 'badge-slate',
        };
    }
}
