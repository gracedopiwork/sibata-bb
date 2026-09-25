@extends('layouts.app')

@section('title', 'Daftar BB')
@section('heading', 'Daftar Barang Bukti')
@section('subheading', 'Setiap BB berdiri sendiri dan bisa dicari, termasuk yang ada di dalam segel')

@section('content')
@include('partials.inventory-tabs')
<form class="filter-bar" method="GET">
    <div class="min-w-64 flex-1">
        <label class="label" for="q">Cari</label>
        <input id="q" class="field" name="q" value="{{ request('q') }}" placeholder="Nama BB, kode segel, perkara, terdakwa">
    </div>
    <div class="w-52">
        <label class="label" for="category">Jenis</label>
        <select id="category" class="field" name="category">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->code }}" @selected(request('category') === $category->code)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="w-52">
        <label class="label" for="status">Status gudang</label>
        <select id="status" class="field" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-outline">Cari</button>
</form>

<div class="space-y-4">
    @forelse ($items as $item)
        <article class="card">
            <div class="flex flex-col gap-5 md:flex-row">
                @if($item->physicalUnit?->hasPhoto())
                    <img class="h-24 w-24 shrink-0 rounded-xl object-cover ring-1 ring-navy-100" src="{{ $item->physicalUnit->photoUrl() }}" alt="{{ $item->physicalUnit->unit_code }}">
                @else
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-navy-50 text-xs font-semibold text-navy-400 ring-1 ring-navy-100">Tanpa foto</div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a class="font-serif text-lg leading-snug text-navy-900 hover:text-gold-600" href="{{ route('items.show', $item) }}">{{ $item->displayName() }}</a>
                            <p class="mt-1 text-sm text-navy-500">{{ $item->categoryLabel() }} · {{ $item->quantity }}</p>
                        </div>
                        @if($item->physicalUnit)
                            <span class="{{ $item->physicalUnit->current_status->badgeClass() }}">{{ $item->physicalUnit->current_status->label() }}</span>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Segel / unit</p>
                            @if($item->physicalUnit)
                                <a class="mt-1 block font-semibold hover:text-gold-600" href="{{ route('units.show', $item->physicalUnit) }}">{{ $item->physicalUnit->unit_code }}</a>
                                <p class="text-sm text-navy-500">{{ $item->physicalUnit->unit_type->label() }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Perkara</p>
                            <p class="mt-1 break-words font-medium">{{ $item->physicalUnit?->legalCase?->case_number }}</p>
                            <p class="text-sm text-navy-600">{{ $item->physicalUnit?->legalCase?->defendant_name }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Lokasi</p>
                            <p class="mt-1 font-medium">{{ $item->physicalUnit?->storageLocation?->name ?? $item->physicalUnit?->storage_location }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="card py-12 text-center text-navy-500">Tidak ada barang bukti.</div>
    @endforelse
</div>

<div class="mt-4">{{ $items->links() }}</div>
@endsection
