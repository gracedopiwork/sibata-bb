<?php

namespace App\Imports;

use App\Enums\EvidenceStatus;
use App\Models\EvidenceItem;
use App\Models\User;
use App\Services\EvidenceLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EvidenceImport implements ToCollection, WithHeadingRow
{
    private int $imported = 0;

    private int $failed = 0;

    public function __construct(private readonly User $actor) {}

    public function collection(Collection $rows): void
    {
        $service = app(EvidenceLogService::class);

        foreach ($rows as $row) {
            $noReg = trim((string) ($row['no_reg_bb'] ?? ''));

            if ($noReg === '') {
                continue;
            }

            if (EvidenceItem::query()->where('no_reg_bb', $noReg)->exists()) {
                $this->failed++;

                continue;
            }

            $statusValue = strtoupper(trim((string) ($row['status'] ?? EvidenceStatus::Tersedia->value)));
            $status = EvidenceStatus::tryFrom($statusValue) ?? EvidenceStatus::Tersedia;

            DB::transaction(function () use ($row, $noReg, $status, $service): void {
                $item = EvidenceItem::query()->create([
                    'qr_token' => EvidenceItem::nextQrToken(),
                    'no_reg_bb' => $noReg,
                    'no_reg_perkara' => trim((string) ($row['no_reg_perkara'] ?? '-')),
                    'nama_terdakwa' => trim((string) ($row['nama_terdakwa'] ?? '-')),
                    'nama_barang' => trim((string) ($row['nama_barang'] ?? '-')),
                    'jumlah_satuan' => trim((string) ($row['jumlah_satuan'] ?? '-')),
                    'lokasi_rak' => trim((string) ($row['lokasi_rak'] ?? '-')),
                    'status' => $status,
                ]);

                $service->register($item, $this->actor, 'Impor massal gudang.');
                $this->imported++;
            });
        }
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    public function failedCount(): int
    {
        return $this->failed;
    }
}
