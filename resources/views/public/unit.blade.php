<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $unit->unit_code }} — SITABA-BB Kejari Wajo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sitaba-bb-mark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700|source-serif-4:600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-parchment">
    <div class="mx-auto max-w-lg px-4 py-10">
        <img src="{{ asset('images/logo-sitaba-bb.png') }}" alt="Logo SITABA-BB" class="mb-4 h-16 w-16 rounded-full bg-white object-cover ring-1 ring-gold-500/40">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-gold-600">Kejaksaan Negeri Wajo · Seksi PB3R</p>
        <h1 class="mt-2 font-serif text-3xl text-navy-900">{{ $unit->unit_code }}</h1>
        <p class="mt-1 text-sm text-navy-600">{{ $unit->unit_type->label() }}</p>

        <div class="card mt-6 space-y-4">
            <div>
                <p class="text-xs uppercase text-navy-500">Nama terdakwa</p>
                <p class="font-semibold">{{ $unit->legalCase?->defendant_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Nomor perkara</p>
                <p class="font-semibold">{{ $unit->legalCase?->case_number }}</p>
            </div>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase text-navy-500">Status gudang</p>
                    <span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span>
                </div>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Lokasi penyimpanan</p>
                <p class="font-semibold">{{ $unit->storage_location }}</p>
            </div>
            @if($unit->hasPhoto())
                <img class="w-full rounded-xl object-cover" src="{{ $unit->photoUrl() }}" alt="Foto {{ $unit->unit_code }}">
            @endif
        </div>

        <div class="card mt-4">
            <h2 class="font-serif text-lg">Isi unit</h2>
            <ul class="mt-3 divide-y divide-navy-100 text-sm">
                @foreach ($unit->items as $item)
                    <li class="py-3">
                        <p class="font-semibold leading-relaxed">{{ $item->displayName() }}</p>
                        <p class="text-navy-600">{{ $item->quantity }} · {{ $item->categoryLabel() }}</p>
                        <p class="text-xs text-navy-500">{{ $item->verdict_status->label() }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</body>
</html>
