<?php

namespace App\Exports;

use App\Models\UnitItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SipRegisterExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Kode Unit',
            'Jenis Unit',
            'No. Perkara',
            'Nama Terdakwa',
            'Nama JPU',
            'Lokasi Gudang',
            'Status Unit',
            'Rincian Barang',
            'Kategori',
            'Jumlah',
            'Status Putusan',
            'No. BA Eksekusi',
            'Penerima',
            'Tanggal Eksekusi',
        ];
    }

    /**
     * @param  UnitItem  $row
     * @return list<string>
     */
    public function map($row): array
    {
        $unit = $row->physicalUnit;
        $case = $unit?->legalCase;

        return [
            $unit?->unit_code ?? '',
            $unit?->unit_type?->value ?? '',
            $case?->case_number ?? '',
            $case?->defendant_name ?? '',
            $case?->prosecutor_name ?? '',
            $unit?->storage_location ?? '',
            $unit?->current_status?->value ?? '',
            $row->item_name,
            $row->category->value,
            $row->quantity,
            $row->verdict_status->value,
            $row->execution_ba_number ?? '',
            $row->execution_recipient ?? '',
            optional($row->execution_date)->format('Y-m-d') ?? '',
        ];
    }
}
