@extends('layouts.app')

@section('title', 'Daftar Segel')
@section('heading', 'Daftar Segel / Paket')
@section('subheading', 'Wadah tersegel. Isi BB tetap bisa dicari satu per satu di Daftar BB')

@section('content')
<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <input class="field w-72" name="q" value="{{ request('q') }}" placeholder="Kode PKT, perkara, terdakwa, isi">
    <select class="field w-48" name="status">
        <option value="">Semua status gudang</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <button class="btn-outline">Cari</button>
    <a class="btn-gold" href="{{ route('items.index') }}">Daftar BB</a>
</form>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Kode segel</th>
                <th class="px-4 py-3">Perkara</th>
                <th class="px-4 py-3">Isi BB</th>
                <th class="px-4 py-3">Lokasi</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($units as $unit)
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @if($unit->hasPhoto())
                                <img class="h-12 w-12 rounded-lg object-cover" src="{{ $unit->photoUrl() }}" alt="{{ $unit->unit_code }}">
                            @endif
                            <div>
                                <a class="font-semibold" href="{{ route('units.show', $unit) }}">{{ $unit->unit_code }}</a>
                                <p class="text-xs text-navy-500">{{ $unit->items->count() }} barang di dalam segel</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <p>{{ $unit->legalCase?->case_number }}</p>
                        <p class="text-xs text-navy-500">{{ $unit->legalCase?->defendant_name }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <ul class="space-y-1">
                            @foreach ($unit->items as $item)
                                <li>
                                    <a class="font-semibold hover:text-gold-600" href="{{ route('items.show', $item) }}">{{ $item->item_name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="px-4 py-3">{{ $unit->storage_location }}</td>
                    <td class="px-4 py-3"><span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-navy-500">Belum ada segel.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $units->links() }}</div>
</div>
@endsection
