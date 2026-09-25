<?php

namespace App\Enums;

enum VerdictStatus: string
{
    case MenungguPutusan = 'MENUNGGU_PUTUSAN';
    case Dimusnahkan = 'DIMUSNAHKAN';
    case Dikembalikan = 'DIKEMBALIKAN';
    case DirampasNegaraLelang = 'DIRAMPAS_NEGARA_LELANG';
    case Psp = 'PSP';

    public function label(): string
    {
        return match ($this) {
            self::MenungguPutusan => 'Menunggu putusan',
            self::Dimusnahkan => 'Dimusnahkan',
            self::Dikembalikan => 'Dikembalikan',
            self::DirampasNegaraLelang => 'Dirampas negara / lelang',
            self::Psp => 'PSP',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::MenungguPutusan;
    }
}
