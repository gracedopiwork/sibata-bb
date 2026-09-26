@extends('layouts.app')

@section('title', $case->case_number)
@section('heading', $case->case_number)
@section('subheading', $case->defendant_name.' · '.$case->prosecutor_name)

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn-outline" href="{{ route('cases.edit', $case) }}">Ubah header</a>
    @if($case->physicalUnits->isNotEmpty())
        <a class="btn-gold" href="{{ route('print-labels.sheet', ['ids' => $case->physicalUnits->pluck('id')->implode(',')]) }}">Cetak / cetak ulang semua label</a>
    @endif
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-1">
        <p class="text-xs font-bold uppercase tracking-wide text-navy-500">Status perkara</p>
        <p class="mt-2 font-serif text-2xl">{{ $case->case_status->label() }}</p>
        <p class="mt-2 text-sm text-navy-600">Jenis: {{ $case->caseType?->name ?? '—' }}</p>
        <p class="mt-1 text-sm text-navy-600">JPU: {{ $case->prosecutor_name }}</p>
        @if($case->notes)
            <p class="mt-4 text-sm text-navy-600">{{ $case->notes }}</p>
        @endif
    </div>
    <div class="card lg:col-span-2">
        <h3 class="font-serif text-lg">Daftar barang bukti</h3>
        <p class="mt-1 text-xs text-navy-500">Satu perkara boleh punya BB mandiri (BB-…) dan paket/segel (PKT-…) sekaligus.</p>
        <div class="mt-4 space-y-5">
            @forelse ($case->physicalUnits as $unit)
                <article class="rounded-2xl border border-navy-100 bg-navy-50/40 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 gap-3">
                            @if($unit->hasPhoto())
                                <a href="{{ route('units.show', $unit) }}" class="shrink-0">
                                    <img class="h-16 w-16 rounded-lg object-cover" src="{{ $unit->photoUrl() }}" alt="Foto {{ $unit->unit_code }}">
                                </a>
                            @endif
                            <div class="min-w-0">
                                <a class="font-semibold text-navy-900" href="{{ route('units.show', $unit) }}">{{ $unit->unit_code }}</a>
                                <p class="text-xs text-navy-500">{{ $unit->unit_type->label() }} · {{ $unit->storageLocation?->name ?? $unit->storage_location }}</p>
                                @unless($unit->hasPhoto())
                                    <p class="mt-1 text-xs text-navy-500">Belum ada foto — unggah di halaman unit.</p>
                                @endunless
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span>
                            <a class="text-sm font-semibold text-navy-800 hover:text-gold-600" href="{{ route('print-labels.sheet', ['ids' => $unit->id]) }}">
                                {{ $unit->is_printed ? 'Cetak ulang' : 'Cetak label' }}
                            </a>
                        </div>
                    </div>

                    <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm">
                        @forelse ($unit->items as $item)
                            <li class="pl-1">
                                <a class="item-copy font-medium text-navy-900 hover:text-gold-600" href="{{ route('items.show', $item) }}">{{ $item->displayName() }}</a>
                                <p class="mt-0.5 text-xs text-navy-600">
                                    {{ $item->categoryLabel() }}
                                    @if($item->quantity)
                                        · {{ $item->quantity }}
                                    @endif
                                    · {{ $item->verdict_status->label() }}
                                </p>
                            </li>
                        @empty
                            <li class="text-navy-500">Isi belum dicatat.</li>
                        @endforelse
                    </ol>
                </article>
            @empty
                <p class="py-4 text-sm text-navy-500">Belum ada barang bukti.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-4">
    @include('loans._history', [
        'loans' => $loans,
        'showUnit' => true,
        'heading' => 'Peminjaman BB perkara ini',
        'subheading' => 'Riwayat pinjam dan kembali untuk semua unit dalam perkara ini',
        'emptyText' => 'Belum ada peminjaman BB untuk perkara ini.',
    ])
</div>

@if(!empty($printIds))
<div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/50 p-4">
    <div class="card max-w-lg">
        <h3 class="font-serif text-xl">Label QR siap dicetak</h3>
        <p class="mt-2 text-sm text-navy-600">{{ count($printIds) }} unit baru memiliki kode stiker. Cetak sekarang atau nanti dari antrean.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a class="btn-gold" href="{{ route('print-labels.sheet', ['ids' => implode(',', $printIds)]) }}">Cetak label</a>
            <button type="button" class="btn-outline" @click="open = false">Nanti</button>
        </div>
    </div>
</div>
@endif
@endsection
