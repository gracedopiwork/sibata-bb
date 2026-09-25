<?php

namespace App\Enums;

enum EvidenceActionType: string
{
    case Register = 'REGISTER';
    case Pinjam = 'PINJAM';
    case Kembali = 'KEMBALI';
    case Relokasi = 'RELOKASI';
    case Eksekusi = 'EKSEKUSI';

    public function label(): string
    {
        return match ($this) {
            self::Register => 'Registrasi',
            self::Pinjam => 'Peminjaman',
            self::Kembali => 'Pengembalian',
            self::Relokasi => 'Relokasi',
            self::Eksekusi => 'Eksekusi',
        };
    }
}
