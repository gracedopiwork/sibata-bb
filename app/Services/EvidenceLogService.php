<?php

namespace App\Services;

use App\Enums\EvidenceActionType;
use App\Enums\EvidenceStatus;
use App\Models\EvidenceItem;
use App\Models\EvidenceLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvidenceLogService
{
    public function register(EvidenceItem $item, ?User $actor, ?string $notes = null): EvidenceLog
    {
        return $this->write($item, $actor, EvidenceActionType::Register, [
            'notes' => $notes ?? 'Barang bukti didaftarkan ke gudang PB3R.',
        ]);
    }

    public function loan(
        EvidenceItem $item,
        ?User $actor,
        EvidenceStatus $status,
        string $borrowerName,
        string $purpose,
        ?string $expectedReturnDate = null,
        ?string $notes = null,
    ): EvidenceLog {
        return DB::transaction(function () use ($item, $actor, $status, $borrowerName, $purpose, $expectedReturnDate, $notes) {
            $item->update(['status' => $status]);

            return $this->write($item, $actor, EvidenceActionType::Pinjam, [
                'borrower_name' => $borrowerName,
                'purpose' => $purpose,
                'expected_return_date' => $expectedReturnDate,
                'notes' => $notes,
            ]);
        });
    }

    public function returnToWarehouse(
        EvidenceItem $item,
        ?User $actor,
        ?string $photoPath = null,
        ?string $notes = null,
    ): EvidenceLog {
        return DB::transaction(function () use ($item, $actor, $photoPath, $notes) {
            $item->update(['status' => EvidenceStatus::Tersedia]);

            return $this->write($item, $actor, EvidenceActionType::Kembali, [
                'photo_proof_path' => $photoPath,
                'notes' => $notes ?? 'Barang bukti dikembalikan ke gudang.',
            ]);
        });
    }

    public function relocate(EvidenceItem $item, ?User $actor, string $oldLocation, string $newLocation): EvidenceLog
    {
        return DB::transaction(function () use ($item, $actor, $oldLocation, $newLocation) {
            $item->update(['lokasi_rak' => $newLocation]);

            return $this->write($item, $actor, EvidenceActionType::Relokasi, [
                'notes' => sprintf('Relokasi dari %s ke %s.', $oldLocation, $newLocation),
            ]);
        });
    }

    public function execute(EvidenceItem $item, ?User $actor, EvidenceStatus $status, ?string $notes = null): EvidenceLog
    {
        return DB::transaction(function () use ($item, $actor, $status, $notes) {
            $item->update(['status' => $status]);

            return $this->write($item, $actor, EvidenceActionType::Eksekusi, [
                'notes' => $notes ?? ('Eksekusi: '.$status->label()),
            ]);
        });
    }

    public function amend(EvidenceItem $item, ?User $actor, string $notes): EvidenceLog
    {
        return $this->write($item, $actor, EvidenceActionType::Register, [
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array{
     *     borrower_name?: string|null,
     *     purpose?: string|null,
     *     expected_return_date?: string|null,
     *     photo_proof_path?: string|null,
     *     notes?: string|null
     * }  $attributes
     */
    private function write(
        EvidenceItem $item,
        ?User $actor,
        EvidenceActionType $action,
        array $attributes = [],
    ): EvidenceLog {
        return $item->logs()->create([
            'user_id' => $actor?->id,
            'action_type' => $action,
            'borrower_name' => $attributes['borrower_name'] ?? null,
            'purpose' => $attributes['purpose'] ?? null,
            'expected_return_date' => $attributes['expected_return_date'] ?? null,
            'photo_proof_path' => $attributes['photo_proof_path'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);
    }
}
