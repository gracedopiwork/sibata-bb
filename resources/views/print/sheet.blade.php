<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Label SITABA-BB</title>
    <style>
        @page { size: A4; margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0b1f3a; margin: 0; background: #f4efe4; }
        .toolbar { padding: 12px 16px; background: #0b1f3a; color: white; display: flex; gap: 8px; align-items: center; }
        .toolbar button, .toolbar a { background: #c9a227; color: #07111f; border: 0; border-radius: 8px; padding: 8px 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .sheet { display: grid; grid-template-columns: repeat(auto-fill, 70mm); gap: 6mm; padding: 12px; justify-content: center; }
        .label {
            width: 70mm;
            height: 50mm;
            background: white;
            border: 0.4mm solid #0b1f3a;
            overflow: hidden;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        .label-head { background: #0b1f3a; color: #e8c547; text-align: center; padding: 2.5mm 2mm 2mm; }
        .label-head strong { display: block; font-size: 7.5pt; letter-spacing: 0.4px; }
        .label-head span { display: block; font-size: 6.5pt; color: #fff; letter-spacing: 0.3px; }
        .gold { height: 1.2mm; background: #c9a227; }
        .body { display: flex; flex: 1; padding: 2mm; gap: 2mm; }
        .qr { width: 24mm; height: 24mm; object-fit: contain; border: 0.3mm solid #c9a227; padding: 0.6mm; }
        .meta { font-size: 6.5pt; line-height: 1.25; }
        .code { font-size: 9pt; font-weight: 800; letter-spacing: 0.3px; }
        .muted { color: #5b6b80; }
        @media print {
            .toolbar { display: none !important; }
            body { background: white; }
            .sheet { padding: 0; gap: 4mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / cetak ulang</button>
        <form id="mark-printed" method="POST" action="{{ route('print-labels.printed') }}">
            @csrf
            @foreach ($ids as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}">
            @endforeach
            <button type="submit">Tandai sudah dicetak</button>
        </form>
        <a href="{{ route('print-labels.index') }}">Kembali</a>
    </div>
    <div class="sheet">
        @foreach ($units as $unit)
            <article class="label">
                <div class="label-head">
                    <strong>KEJAKSAAN NEGERI WAJO</strong>
                    <span>SEKSI PB3R</span>
                </div>
                <div class="gold"></div>
                <div class="body">
                    <img class="qr" src="{{ $unit->qr_data_uri }}" alt="QR {{ $unit->unit_code }}">
                    <div class="meta">
                        <div class="code">{{ $unit->unit_code }}</div>
                        <div class="muted">No. Perkara</div>
                        <div>{{ \Illuminate\Support\Str::limit($unit->legalCase?->case_number, 28) }}</div>
                        <div class="muted">Terdakwa</div>
                        <div>{{ \Illuminate\Support\Str::limit($unit->legalCase?->defendant_name, 32) }}</div>
                        <div class="muted">Lokasi</div>
                        <div>{{ \Illuminate\Support\Str::limit($unit->storage_location, 28) }}</div>
                        <div class="muted">Isi</div>
                        <div>{{ \Illuminate\Support\Str::limit($unit->itemsSummary(70), 70) }}</div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
    <script>
        window.addEventListener('afterprint', function () {
            document.getElementById('mark-printed').submit();
        });
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
