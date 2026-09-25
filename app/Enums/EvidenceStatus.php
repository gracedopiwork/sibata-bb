<?php

namespace App\Enums;

enum EvidenceStatus: string
{
    case Tersedia = 'TERSEDIA';
    case DipinjamSidang = 'DIPINJAM_SIDANG';
    case PinjamPakai = 'PINJAM_PAKAI';
    case UjiLabForensik = 'UJI_LAB_FORENSIK';
    case SelesaiDimusnahkan = 'SELESAI - DIMUSNAHKAN';
    case SelesaiDikembalikan = 'SELESAI - DIKEMBALIKAN';
    case SelesaiDilelangPnbp = 'SELESAI - DILELANG_PNBP';
    case SelesaiLampirBerkasPsp = 'SELESAI - LAMPIR_BERKAS / PSP';

    public function label(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::DipinjamSidang => 'Dipinjam Sidang',
            self::PinjamPakai => 'Pinjam Pakai',
            self::UjiLabForensik => 'Uji Lab Forensik',
            self::SelesaiDimusnahkan => 'Selesai — Dimusnahkan',
            self::SelesaiDikembalikan => 'Selesai — Dikembalikan',
            self::SelesaiDilelangPnbp => 'Selesai — Dilelang PNBP',
            self::SelesaiLampirBerkasPsp => 'Selesai — Lampir Berkas / PSP',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Tersedia;
    }

    public function isOut(): bool
    {
        return in_array($this, [
            self::DipinjamSidang,
            self::PinjamPakai,
            self::UjiLabForensik,
        ], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [
            self::SelesaiDimusnahkan,
            self::SelesaiDikembalikan,
            self::SelesaiDilelangPnbp,
            self::SelesaiLampirBerkasPsp,
        ], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Tersedia => 'badge-success',
            self::DipinjamSidang => 'badge-warning',
            self::PinjamPakai => 'badge-info',
            self::UjiLabForensik => 'badge-purple',
            self::SelesaiDimusnahkan => 'badge-danger',
            self::SelesaiDikembalikan => 'badge-slate',
            self::SelesaiDilelangPnbp => 'badge-gold',
            self::SelesaiLampirBerkasPsp => 'badge-navy',
        };
    }

    /**
     * @return list<self>
     */
    public static function executionCases(): array
    {
        return [
            self::SelesaiDimusnahkan,
            self::SelesaiDikembalikan,
            self::SelesaiDilelangPnbp,
            self::SelesaiLampirBerkasPsp,
        ];
    }
}
