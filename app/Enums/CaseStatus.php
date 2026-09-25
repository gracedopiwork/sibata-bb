<?php

namespace App\Enums;

enum CaseStatus: string
{
    case Tahap2 = 'TAHAP_2';
    case Sidang = 'SIDANG';
    case Inkracht = 'INKRACHT';
    case Selesai = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::Tahap2 => 'Tahap II',
            self::Sidang => 'Sidang',
            self::Inkracht => 'Inkracht',
            self::Selesai => 'Selesai',
        };
    }
}
