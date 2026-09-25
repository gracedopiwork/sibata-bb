@extends('layouts.app')

@section('title', 'Daftar BB')
@section('heading', 'Daftar Barang Bukti')
@section('subheading', 'Setiap BB berdiri sendiri dan bisa dicari, termasuk yang ada di dalam segel')

@section('content')
<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <input class="field w-72" name="q" value="{{ request('q') }}" placeholder="Nama BB, kode segel, perkara, terdakwa">
    <select class="field w-48" name="category">
        <option value="">Semua kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->code }}" @selected(request('category') === $category->code)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select class="field w-48" name="status">
        <option value="">Semua status gudang</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="btn-outline">Cari</button>
    <a class="btn-gold" href="{{ route('seals.index') }}">Daftar segel</a>
</form>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Barang bukti</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3">Jumlah</th>
                <th class="px-4 py-3">Segel / unit</th>
                <th class="px-4 py-3">Perkara</th>
                <th class="px-4 py-3">Lokasi</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">
                        <a class="font-semibold" href="{{ route('items.show', $item) }}">{{ $item->item_name }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $item->categoryLabel() }}</td>
                    <td class="px-4 py-3">{{ $item->quantity }}</td>
                    <td class="px-4 py-3">
                        @if($item->physicalUnit)
                            <a class="font-semibold" href="{{ route('units.show', $item->physicalUnit) }}">{{ $item->physicalUnit->unit_code }}</a>
                            <p class="text-xs text-navy-500">{{ $item->physicalUnit->unit_type->label() }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p>{{ $item->physicalUnit?->legalCase?->case_number }}</p>
                        <p class="text-xs text-navy-500">{{ $item->physicalUnit?->legalCase?->defendant_name }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $item->physicalUnit?->storageLocation?->name ?? $item->physicalUnit?->storage_location }}</td>
                    <td class="px-4 py-3">
                        @if($item->physicalUnit)
                            <span class="{{ $item->physicalUnit->current_status->badgeClass() }}">{{ $item->physicalUnit->current_status->label() }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-navy-500">Tidak ada barang bukti.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
