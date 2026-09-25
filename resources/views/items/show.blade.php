@extends('layouts.app')

@section('title', $item->displayName())
@section('heading', $item->displayName())
@section('subheading', 'Detail barang bukti · '.($item->physicalUnit?->unit_code ?? 'Tanpa unit'))

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn-outline" href="{{ route('items.index') }}">Kembali ke inventaris</a>
    @if($item->physicalUnit)
        <a class="btn-outline" href="{{ route('units.show', $item->physicalUnit) }}">Buka {{ $item->physicalUnit->unit_type->value === 'PACK' ? 'segel' : 'unit' }}</a>
        @if($item->physicalUnit->legalCase)
            <a class="btn-outline" href="{{ route('cases.show', $item->physicalUnit->legalCase) }}">Buka perkara</a>
        @endif
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 lg:col-span-1">
        <p class="text-xs uppercase text-navy-500">Kategori</p>
        <p class="font-semibold">{{ $item->categoryLabel() }}</p>
        <p class="text-xs uppercase text-navy-500">Jumlah</p>
        <p class="font-semibold">{{ $item->quantity }}</p>
        <p class="text-xs uppercase text-navy-500">Putusan</p>
        <p class="font-semibold">{{ $item->verdict_status->label() }}</p>
        <p class="text-xs uppercase text-navy-500">Segel / unit</p>
        <p class="font-semibold">{{ $item->physicalUnit?->unit_code }}</p>
        <p class="text-xs text-navy-500">{{ $item->physicalUnit?->unit_type->label() }}</p>
        <p class="text-xs uppercase text-navy-500">Perkara</p>
        <p class="font-semibold">{{ $item->physicalUnit?->legalCase?->case_number }}</p>
        <p class="text-sm text-navy-600">{{ $item->physicalUnit?->legalCase?->defendant_name }}</p>
        <p class="text-xs uppercase text-navy-500">Lokasi</p>
        <p class="font-semibold">{{ $item->physicalUnit?->storageLocation?->name ?? $item->physicalUnit?->storage_location }}</p>
        @if($item->physicalUnit)
            <span class="{{ $item->physicalUnit->current_status->badgeClass() }}">{{ $item->physicalUnit->current_status->label() }}</span>
        @endif
        @if($item->physicalUnit?->hasPhoto())
            <img class="mt-2 rounded-xl object-cover" src="{{ $item->physicalUnit->photoUrl() }}" alt="Foto unit">
        @endif
    </div>
    <div class="lg:col-span-2">
        @include('loans._history', ['loans' => $loans])
    </div>
</div>
@endsection
