<?php

namespace App\Enums;

enum ItemCategory: string
{
    case Narkotika = 'NARKOTIKA';
    case Elektronik = 'ELEKTRONIK';
    case Kendaraan = 'KENDARAAN';
    case Senjata = 'SENJATA';
    case Dokumen = 'DOKUMEN';
    case Lainnya = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Narkotika => 'Narkotika',
            self::Elektronik => 'Elektronik',
            self::Kendaraan => 'Kendaraan',
            self::Senjata => 'Senjata',
            self::Dokumen => 'Dokumen',
            self::Lainnya => 'Lainnya',
        };
    }
}
