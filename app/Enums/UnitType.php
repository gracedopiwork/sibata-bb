<?php

namespace App\Enums;

enum UnitType: string
{
    case Single = 'SINGLE';
    case Pack = 'PACK';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'BB Mandiri (Satuan)',
            self::Pack => 'BB Paket (Wadah/Segel)',
        };
    }

    public function codePrefix(): string
    {
        return $this === self::Pack ? 'PKT' : 'BB';
    }
}
