@extends('layouts.app')

@section('title', 'Peminjaman BB')
@section('heading', 'Daftar Peminjaman BB')
@section('subheading', 'Catat pinjam sidang dan pengembalian, lengkap dengan foto kondisi')

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <form class="flex flex-wrap gap-2" method="GET">
        <input class="field w-64" name="q" value="{{ request('q') }}" placeholder="Kode unit, perkara, JPU">
        <select class="field w-44" name="status">
            <option value="">Semua status</option>
            <option value="active" @selected(request('status') === 'active')>Sedang dipinjam</option>
            <option value="returned" @selected(request('status') === 'returned')>Sudah kembali</option>
        </select>
        <button class="btn-outline">Filter</button>
    </form>
    <a class="btn-gold" href="{{ route('loans.create') }}">+ Peminjaman BB</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Unit</th>
                <th class="px-4 py-3">Perkara</th>
                <th class="px-4 py-3">Peminjam</th>
                <th class="px-4 py-3">Sidang</th>
                <th class="px-4 py-3">Foto</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($loans as $loan)
                <tr>
                    <td class="px-4 py-3">
                        <a class="font-semibold" href="{{ route('loans.show', $loan) }}">{{ $loan->physicalUnit?->unit_code }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <p>{{ $loan->physicalUnit?->legalCase?->case_number }}</p>
                        <p class="text-xs text-navy-500">{{ $loan->physicalUnit?->legalCase?->defendant_name }}</p>
                    </td>
                    <td class="px-4 py-3">{{ $loan->borrower_name }}</td>
                    <td class="px-4 py-3">{{ $loan->court_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            @if($loan->hasLoanPhoto())
                                <img class="h-12 w-12 rounded-lg object-cover" src="{{ $loan->loanPhotoUrl() }}" alt="Foto pinjam">
                            @endif
                            @if($loan->hasReturnPhoto())
                                <img class="h-12 w-12 rounded-lg object-cover" src="{{ $loan->returnPhotoUrl() }}" alt="Foto kembali">
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if($loan->isActive())
                            <span class="badge-warning">Dipinjam</span>
                        @else
                            <span class="badge-success">Kembali {{ $loan->returned_at?->timezone(config('app.timezone'))->format('d/m/Y') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-8 text-center text-navy-500" colspan="6">Belum ada peminjaman BB.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $loans->links() }}</div>
</div>
@endsection
