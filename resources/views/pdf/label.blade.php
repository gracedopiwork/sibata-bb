<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 2.5mm; }
        * { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; }
        img { border: 0; }
    </style>
</head>
<body>
    @include('pdf.partials.sticker', ['row' => $row, 'qrMm' => 27])
</body>
</html>
