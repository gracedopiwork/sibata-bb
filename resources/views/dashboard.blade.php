@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard PB3R')
@section('subheading', 'Ringkasan gudang barang bukti Kejaksaan Negeri Wajo')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ([
        ['Perkara', $stats['cases'], 'Register Tahap II'],
        ['Unit fisik', $stats['units'], 'Stiker QR BB / PKT'],
        ['Tersimpan gudang', $stats['gudang'], 'Siap dipinjam sidang'],
        ['Dipinjam sidang', $stats['pinjam'], 'Sedang di PN / persidangan'],
        ['Selesai', $stats['selesai'], 'Seluruh isi sudah dieksekusi'],
        ['Antrean cetak', $stats['unprinted'], 'Label belum dicetak'],
    ] as [$label, $value, $hint])
        <div class="card">
            <p class="text-xs font-bold uppercase tracking-wide text-navy-500">{{ $label }}</p>
            <p class="mt-2 font-serif text-4xl text-navy-900">{{ $value }}</p>
            <p class="mt-1 text-xs text-navy-600">{{ $hint }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-serif text-lg">Mutasi terbaru</h3>
            <a class="text-sm font-semibold text-navy-700 hover:text-gold-600" href="{{ route('units.index') }}">Inventaris</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-wide text-navy-500">
                    <tr>
                        <th class="pb-3">Waktu</th>
                        <th class="pb-3">Jenis</th>
                        <th class="pb-3">Unit</th>
                        <th class="pb-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-navy-100">
                    @forelse ($recentMutations as $mutation)
                        <tr>
                            <td class="py-3 text-navy-600">{{ $mutation->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                            <td class="py-3 font-semibold">{{ $mutation->mutation_type->label() }}</td>
                            <td class="py-3">
                                @if ($mutation->physicalUnit)
                                    <a class="text-navy-800 hover:text-gold-600" href="{{ route('units.show', $mutation->physicalUnit) }}">{{ $mutation->physicalUnit->unit_code }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3">{{ $mutation->handled_by }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-navy-500">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h3 class="font-serif text-lg">Sidang terlewat</h3>
        <p class="mt-1 text-xs text-navy-500">Dipinjam sidang dengan tanggal sidang sudah lewat</p>
        <ul class="mt-4 space-y-3">
            @forelse ($overdue as $unit)
                <li class="rounded-xl bg-amber-50 p-3 text-sm">
                    <a class="font-semibold text-navy-900" href="{{ route('units.show', $unit) }}">{{ $unit->unit_code }}</a>
                    <p class="text-navy-700">{{ $unit->legalCase?->defendant_name }}</p>
                </li>
            @empty
                <li class="text-sm text-navy-500">Tidak ada pinjaman terlambat.</li>
            @endforelse
        </ul>
        <a class="mt-4 inline-block text-sm font-semibold text-navy-700" href="{{ route('print-labels.index') }}">Buka cetak label →</a>
    </div>
</div>
@endsection
