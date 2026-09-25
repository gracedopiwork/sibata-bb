@extends('layouts.app')

@section('title', 'Daftar Segel')
@section('heading', 'Daftar Segel / Paket')
@section('subheading', 'Wadah tersegel. Isi BB tetap bisa dicari satu per satu di Daftar BB')

@section('content')
<form class="filter-bar" method="GET">
    <div class="min-w-64 flex-1">
        <label class="label" for="q">Cari</label>
        <input id="q" class="field" name="q" value="{{ request('q') }}" placeholder="Kode PKT, perkara, terdakwa, isi">
    </div>
    <div class="w-56">
        <label class="label" for="status">Status gudang</label>
        <select id="status" class="field" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-outline">Cari</button>
    <a class="btn-gold" href="{{ route('items.index') }}">Daftar BB</a>
</form>

<div class="space-y-4">
    @forelse ($units as $unit)
        <article class="card">
            <div class="flex flex-col gap-5 md:flex-row">
                @if($unit->hasPhoto())
                    <img class="h-28 w-28 shrink-0 rounded-xl object-cover ring-1 ring-navy-100" src="{{ $unit->photoUrl() }}" alt="{{ $unit->unit_code }}">
                @else
                    <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-xl bg-navy-50 text-xs font-semibold text-navy-400 ring-1 ring-navy-100">Tanpa foto</div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <a class="font-serif text-xl text-navy-900 hover:text-gold-600" href="{{ route('units.show', $unit) }}">{{ $unit->unit_code }}</a>
                            <p class="mt-1 text-sm text-navy-500">{{ $unit->items->count() }} barang di dalam segel</p>
                        </div>
                        <span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Perkara</p>
                            <p class="mt-1 font-medium break-words">{{ $unit->legalCase?->case_number }}</p>
                            <p class="text-sm text-navy-600">{{ $unit->legalCase?->defendant_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Lokasi</p>
                            <p class="mt-1 font-medium">{{ $unit->storageLocation?->name ?? $unit->storage_location }}</p>
                        </div>
                    </div>

                    <ol class="mt-4 list-decimal space-y-2 pl-5">
                        @forelse ($unit->items as $item)
                            <li class="item-copy">
                                <a class="font-medium hover:text-gold-600" href="{{ route('items.show', $item) }}">{{ $item->displayName() }}</a>
                                <span class="text-navy-500"> · {{ $item->categoryLabel() }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-navy-500">Isi belum dicatat.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </article>
    @empty
        <div class="card py-12 text-center text-navy-500">Belum ada segel.</div>
    @endforelse
</div>

<div class="mt-4">{{ $units->links() }}</div>
@endsection
