<?php

namespace App\Exports;

use App\Models\EvidenceLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LoanRegisterExport implements FromQuery, WithHeadings, WithMapping
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
            'Waktu',
            'Token QR',
            'No. Reg BB',
            'Terdakwa',
            'Barang',
            'Peminjam',
            'Keperluan',
            'Estimasi Kembali',
            'Petugas',
            'Status BB Saat Ini',
        ];
    }

    /**
     * @param  EvidenceLog  $row
     * @return list<string>
     */
    public function map($row): array
    {
        return [
            optional($row->created_at)->format('Y-m-d H:i'),
            $row->evidence?->qr_token,
            $row->evidence?->no_reg_bb,
            $row->evidence?->nama_terdakwa,
            $row->evidence?->nama_barang,
            $row->borrower_name,
            $row->purpose,
            optional($row->expected_return_date)->format('Y-m-d'),
            $row->user?->name,
            $row->evidence?->status?->value,
        ];
    }
}
