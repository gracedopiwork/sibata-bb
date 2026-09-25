<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1, h2 { text-align: center; margin: 0; }
        h1 { font-size: 14px; }
        h2 { font-size: 12px; margin-top: 4px; }
        .meta { text-align: center; margin: 6px 0 12px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; }
        th { background: #0b1f3a; color: #fff; font-size: 8px; text-transform: uppercase; }
        td { font-size: 8px; }
    </style>
</head>
<body>
    <h1>KEJAKSAAN NEGERI WAJO</h1>
    <h2>REGISTER PEMINJAMAN BARANG BUKTI</h2>
    <p class="meta">
        Seksi PB3R
        @if($filters['from'] || $filters['to'])
            · Periode {{ $filters['from'] ?? '...' }} s.d. {{ $filters['to'] ?? '...' }}
        @endif
        · Dicetak {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>Token / No. Reg BB</th>
                <th>Terdakwa</th>
                <th>Barang</th>
                <th>Peminjam</th>
                <th>Keperluan</th>
                <th>Est. Kembali</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $i => $log)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->evidence?->qr_token }}<br>{{ $log->evidence?->no_reg_bb }}</td>
                    <td>{{ $log->evidence?->nama_terdakwa }}</td>
                    <td>{{ $log->evidence?->nama_barang }}</td>
                    <td>{{ $log->borrower_name }}</td>
                    <td>{{ $log->purpose }}</td>
                    <td>{{ $log->expected_return_date?->format('d/m/Y') }}</td>
                    <td>{{ $log->user?->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
