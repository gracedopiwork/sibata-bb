<?php

namespace App\Exports;

use App\Models\EvidenceItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EvidenceExport implements FromQuery, WithHeadings, WithMapping
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
            'Token QR',
            'No. Reg BB',
            'No. Reg Perkara',
            'Nama Terdakwa',
            'Nama Barang',
            'Jumlah/Satuan',
            'Lokasi Rak',
            'Status',
            'Terdaftar',
        ];
    }

    /**
     * @param  EvidenceItem  $row
     * @return list<string>
     */
    public function map($row): array
    {
        return [
            $row->qr_token,
            $row->no_reg_bb,
            $row->no_reg_perkara,
            $row->nama_terdakwa,
            $row->nama_barang,
            $row->jumlah_satuan,
            $row->lokasi_rak,
            $row->status->value,
            optional($row->created_at)->format('Y-m-d H:i'),
        ];
    }
}
