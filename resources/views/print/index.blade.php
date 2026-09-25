@extends('layouts.app')

@section('title', 'Antrean Cetak Label')
@section('heading', 'Antrean Cetak Label QR')
@section('subheading', 'Stiker 70×50 mm — KEJAKSAAN NEGERI WAJO · SEKSI PB3R')

@section('content')
<form method="GET" action="{{ route('print-labels.sheet') }}" class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <button class="btn-gold" type="submit">Cetak / cetak ulang yang dipilih</button>
        <a class="btn-outline" href="{{ route('print-labels.index', ['all' => 1]) }}">Termasuk sudah dicetak</a>
        <a class="btn-outline" href="{{ route('print-labels.index') }}">Hanya belum dicetak</a>
    </div>
    <div class="card overflow-x-auto p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-navy-50 text-left text-xs uppercase text-navy-600">
                <tr>
                    <th class="px-4 py-3"></th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Perkara</th>
                    <th class="px-4 py-3">Terdakwa</th>
                    <th class="px-4 py-3">Lokasi</th>
                    <th class="px-4 py-3">Status cetak</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-100">
                @forelse ($units as $unit)
                    <tr>
                        <td class="px-4 py-3">
                            <input type="checkbox" name="ids[]" value="{{ $unit->id }}" @checked(! $unit->is_printed || in_array($unit->id, $selectedIds, true))>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $unit->unit_code }}</td>
                        <td class="px-4 py-3">{{ $unit->legalCase?->case_number }}</td>
                        <td class="px-4 py-3">{{ $unit->legalCase?->defendant_name }}</td>
                        <td class="px-4 py-3">{{ $unit->storage_location }}</td>
                        <td class="px-4 py-3">{{ $unit->is_printed ? 'Sudah' : 'Belum' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-semibold text-navy-800 hover:text-gold-600" href="{{ route('print-labels.sheet', ['ids' => $unit->id]) }}">
                                {{ $unit->is_printed ? 'Cetak ulang' : 'Cetak' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-navy-500">Tidak ada unit dalam antrean.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $units->links() }}</div>
    </div>
</form>
@endsection
