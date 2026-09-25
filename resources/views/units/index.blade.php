@extends('layouts.app')

@section('title', 'Inventaris Fisik')
@section('heading', 'Inventaris Fisik BB')
@section('subheading', 'Setiap baris adalah wadah/unit yang menempel stiker QR')

@section('content')
<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <input class="field w-64" name="q" value="{{ request('q') }}" placeholder="Kode, terdakwa, perkara, isi">
    <select class="field w-48" name="status">
        <option value="">Semua status gudang</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select class="field w-44" name="type">
        <option value="">Semua jenis</option>
        @foreach ($types as $type)
            <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
    <button class="btn-outline">Filter</button>
    <a class="btn-gold" href="{{ route('print-labels.index') }}">Antrean cetak</a>
</form>

<div class="card overflow-x-auto p-0">
    <table class="min-w-[1100px] w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Perkara</th>
                <th class="px-4 py-3">Terdakwa</th>
                <th class="px-4 py-3">Jenis aset</th>
                <th class="px-4 py-3">Lokasi</th>
                <th class="px-4 py-3">Isi</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Cetak</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($units as $unit)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 align-top">
                        <a class="font-semibold" href="{{ route('units.show', $unit) }}">{{ $unit->unit_code }}</a>
                        <p class="text-xs text-navy-500">{{ $unit->unit_type->label() }}</p>
                    </td>
                    <td class="max-w-xs break-words px-4 py-3 align-top">{{ $unit->legalCase?->case_number }}</td>
                    <td class="max-w-xs break-words px-4 py-3 align-top">{{ $unit->legalCase?->defendant_name }}</td>
                    <td class="whitespace-nowrap px-4 py-3 align-top">{{ $unit->assetType?->name ?? '—' }}</td>
                    <td class="px-4 py-3 align-top">{{ $unit->storageLocation?->name ?? $unit->storage_location }}</td>
                    <td class="item-copy max-w-sm px-4 py-3 align-top">{{ $unit->itemsSummary(120) }}</td>
                    <td class="px-4 py-3"><span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span></td>
                    <td class="px-4 py-3">
                        <a class="font-semibold text-navy-800 hover:text-gold-600" href="{{ route('print-labels.sheet', ['ids' => $unit->id]) }}">
                            {{ $unit->is_printed ? 'Cetak ulang' : 'Cetak' }}
                        </a>
                        <p class="text-xs text-navy-500">{{ $unit->is_printed ? 'Sudah pernah dicetak' : 'Belum dicetak' }}</p>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-navy-500">Inventaris kosong.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $units->links() }}</div>
</div>
@endsection
