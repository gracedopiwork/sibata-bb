<?php

namespace App\Services;

use App\Enums\ItemCategory;
use App\Enums\MutationType;
use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Enums\VerdictStatus;
use App\Models\LegalCase;
use App\Models\Mutation;
use App\Models\PhysicalUnit;
use App\Models\UnitItem;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function createSingleUnit(
        LegalCase $case,
        string $itemName,
        ItemCategory|string $category,
        string $quantity,
        string $storageLocation,
        ?string $photoPath,
        string $handledBy,
        ?int $assetTypeId = null,
        ?int $storageLocationId = null,
    ): PhysicalUnit {
        $categoryCode = $category instanceof ItemCategory ? $category->value : $category;

        return DB::transaction(function () use ($case, $itemName, $categoryCode, $quantity, $storageLocation, $photoPath, $handledBy, $assetTypeId, $storageLocationId) {
            $unit = PhysicalUnit::query()->create([
                'case_id' => $case->id,
                'unit_code' => PhysicalUnit::nextUnitCode(UnitType::Single),
                'unit_type' => UnitType::Single,
                'asset_type_id' => $assetTypeId,
                'storage_location' => $storageLocation,
                'storage_location_id' => $storageLocationId,
                'photo_path' => $photoPath,
                'current_status' => UnitStatus::TersimpanGudang,
            ]);

            $unit->items()->create([
                'item_name' => $itemName,
                'category' => $categoryCode,
                'quantity' => $quantity,
                'verdict_status' => VerdictStatus::MenungguPutusan,
            ]);

            $this->mutate($unit, MutationType::CheckIn, $handledBy, 'Check-in BB mandiri.');

            return $unit->load(['legalCase', 'items']);
        });
    }

    public function createPackUnit(
        LegalCase $case,
        string $storageLocation,
        ?string $photoPath,
        string $handledBy,
        array $children,
        ?int $assetTypeId = null,
        ?int $storageLocationId = null,
    ): PhysicalUnit {
        return DB::transaction(function () use ($case, $storageLocation, $photoPath, $handledBy, $children, $assetTypeId, $storageLocationId) {
            $unit = PhysicalUnit::query()->create([
                'case_id' => $case->id,
                'unit_code' => PhysicalUnit::nextUnitCode(UnitType::Pack),
                'unit_type' => UnitType::Pack,
                'asset_type_id' => $assetTypeId,
                'storage_location' => $storageLocation,
                'storage_location_id' => $storageLocationId,
                'photo_path' => $photoPath,
                'current_status' => UnitStatus::TersimpanGudang,
            ]);

            foreach ($children as $child) {
                $category = $child['category'];
                $unit->items()->create([
                    'item_name' => $child['item_name'],
                    'category' => $category instanceof ItemCategory ? $category->value : $category,
                    'quantity' => $child['quantity'] ?? '1',
                    'verdict_status' => VerdictStatus::MenungguPutusan,
                ]);
            }

            $this->mutate($unit, MutationType::CheckIn, $handledBy, 'Check-in paket/wadah BB.');

            return $unit->load(['legalCase', 'items']);
        });
    }

    public function addPackChild(PhysicalUnit $unit, string $itemName, ItemCategory|string $category, string $quantity = '1'): UnitItem
    {
        if ($unit->unit_type !== UnitType::Pack) {
            throw new \RuntimeException('Hanya unit paket yang dapat menerima rincian isi.');
        }

        return $unit->items()->create([
            'item_name' => $itemName,
            'category' => $category instanceof ItemCategory ? $category->value : $category,
            'quantity' => $quantity,
            'verdict_status' => VerdictStatus::MenungguPutusan,
        ]);
    }

    public function loan(PhysicalUnit $unit, string $borrowerName, ?string $courtDate, string $handledBy, ?string $notes = null): Mutation
    {
        if ($unit->current_status !== UnitStatus::TersimpanGudang) {
            throw new \RuntimeException('Unit tidak dalam status tersimpan gudang.');
        }

        return DB::transaction(function () use ($unit, $borrowerName, $courtDate, $handledBy, $notes) {
            $unit->update(['current_status' => UnitStatus::DipinjamSidang]);

            return $this->mutate($unit, MutationType::PinjamSidang, $handledBy, $notes, $borrowerName, $courtDate);
        });
    }

    public function returnToWarehouse(PhysicalUnit $unit, string $storageLocation, string $handledBy, ?string $notes = null, ?int $storageLocationId = null): Mutation
    {
        if ($unit->current_status !== UnitStatus::DipinjamSidang) {
            throw new \RuntimeException('Unit tidak sedang dipinjam sidang.');
        }

        return DB::transaction(function () use ($unit, $storageLocation, $handledBy, $notes, $storageLocationId) {
            $unit->update([
                'current_status' => UnitStatus::TersimpanGudang,
                'storage_location' => $storageLocation,
                'storage_location_id' => $storageLocationId,
            ]);

            return $this->mutate($unit, MutationType::KembaliGudang, $handledBy, $notes);
        });
    }

    public function executeItem(
        UnitItem $item,
        VerdictStatus $verdict,
        string $handledBy,
        ?string $baNumber = null,
        ?string $recipient = null,
        ?string $nik = null,
        ?string $photoPath = null,
        ?string $notes = null,
    ): void {
        DB::transaction(function () use ($item, $verdict, $handledBy, $baNumber, $recipient, $nik, $photoPath, $notes) {
            $item->update([
                'verdict_status' => $verdict,
                'execution_ba_number' => $baNumber,
                'execution_recipient' => $recipient,
                'execution_recipient_nik' => $nik,
                'execution_date' => now()->toDateString(),
                'execution_proof_photo' => $photoPath,
            ]);

            $unit = $item->physicalUnit()->first();

            if ($unit === null) {
                return;
            }

            $pending = $unit->items()->where('verdict_status', VerdictStatus::MenungguPutusan->value)->exists();

            if (! $pending) {
                $unit->update(['current_status' => UnitStatus::Selesai]);
            }

            $this->mutate(
                $unit,
                MutationType::Eksekusi,
                $handledBy,
                $notes ?? ($item->item_name.' — '.$verdict->label()),
            );
        });
    }

    public function parseUnitCode(?string $text): ?string
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        if (preg_match('/\b((?:BB|PKT)-\d{4}-\d{3,})\b/i', $text, $matches) === 1) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    public function findByCode(?string $code): ?PhysicalUnit
    {
        $normalized = $this->parseUnitCode($code) ?? (is_string($code) ? strtoupper(trim($code)) : null);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        return PhysicalUnit::query()
            ->with(['legalCase', 'items'])
            ->where('unit_code', $normalized)
            ->first();
    }

    private function mutate(
        PhysicalUnit $unit,
        MutationType $type,
        string $handledBy,
        ?string $notes = null,
        ?string $borrowerName = null,
        ?string $courtDate = null,
    ): Mutation {
        return $unit->mutations()->create([
            'mutation_type' => $type,
            'borrower_name' => $borrowerName,
            'court_date' => $courtDate,
            'notes' => $notes,
            'handled_by' => $handledBy,
        ]);
    }
}
