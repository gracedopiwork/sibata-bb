@extends('layouts.app')

@section('title', 'Laporan')
@section('heading', 'Laporan & Ekspor Register')
@section('subheading', 'Register PB3R per rincian barang bukti')

@section('content')
<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <input class="field w-64" name="q" value="{{ request('q') }}" placeholder="Cari kode / terdakwa / barang">
    <select class="field w-48" name="unit_status">
        <option value="">Status unit</option>
        @foreach ($unitStatuses as $status)
            <option value="{{ $status->value }}" @selected(request('unit_status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select class="field w-52" name="verdict_status">
        <option value="">Status putusan</option>
        @foreach ($verdicts as $verdict)
            <option value="{{ $verdict->value }}" @selected(request('verdict_status') === $verdict->value)>{{ $verdict->label() }}</option>
        @endforeach
    </select>
    <button class="btn-outline">Filter</button>
    <a class="btn-gold" href="{{ route('reports.excel', request()->query()) }}">Ekspor Excel</a>
</form>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase text-navy-600">
            <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Perkara</th>
                <th class="px-4 py-3">Terdakwa</th>
                <th class="px-4 py-3">Barang</th>
                <th class="px-4 py-3">Unit</th>
                <th class="px-4 py-3">Putusan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">
                        <a class="font-semibold" href="{{ route('units.show', $item->physicalUnit) }}">{{ $item->physicalUnit?->unit_code }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $item->physicalUnit?->legalCase?->case_number }}</td>
                    <td class="px-4 py-3">{{ $item->physicalUnit?->legalCase?->defendant_name }}</td>
                    <td class="px-4 py-3">{{ $item->item_name }}</td>
                    <td class="px-4 py-3">
                        @if($item->physicalUnit)
                            <span class="{{ $item->physicalUnit->current_status->badgeClass() }}">{{ $item->physicalUnit->current_status->label() }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->verdict_status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-navy-500">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
