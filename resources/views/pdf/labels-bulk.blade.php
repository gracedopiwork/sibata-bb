<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 8mm 10mm 8mm; }
        * { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #0b1f3a; }
        .sheet-title { font-size: 12px; font-weight: bold; text-align: center; letter-spacing: 0.4px; }
        .sheet-sub { font-size: 8px; text-align: center; color: #5b6b80; margin: 2px 0 8px 0; }
        img { border: 0; }
    </style>
</head>
<body>
    <div class="sheet-title">KEJAKSAAN NEGERI WAJO — SEKSI PB3R</div>
    <div class="sheet-sub">Lembar stiker barang bukti · {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $items->count() }} stiker</div>

    <table width="100%" cellspacing="6" cellpadding="0">
        @foreach ($items->chunk(2) as $pair)
            <tr>
                @foreach ($pair as $row)
                    <td width="50%" valign="top">
                        @include('pdf.partials.sticker', ['row' => $row, 'qrMm' => 24])
                    </td>
                @endforeach
                @if ($pair->count() === 1)
                    <td width="50%">&nbsp;</td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
