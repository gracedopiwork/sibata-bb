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
    <h2>REGISTER BARANG BUKTI — FORM B-4</h2>
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
                <th>Token QR</th>
                <th>No. Reg BB</th>
                <th>No. Reg Perkara</th>
                <th>Terdakwa</th>
                <th>Nama Barang</th>
                <th>Jml</th>
                <th>Rak</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->qr_token }}</td>
                    <td>{{ $item->no_reg_bb }}</td>
                    <td>{{ $item->no_reg_perkara }}</td>
                    <td>{{ $item->nama_terdakwa }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td>{{ $item->jumlah_satuan }}</td>
                    <td>{{ $item->lokasi_rak }}</td>
                    <td>{{ $item->status->value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
