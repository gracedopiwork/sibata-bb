@extends('layouts.app')

@section('title', 'Peminjaman BB')
@section('heading', 'Daftar Peminjaman BB')
@section('subheading', 'Catat pinjam sidang dan pengembalian, lengkap dengan foto kondisi')

@section('content')
<form class="filter-bar" method="GET">
    <div class="min-w-64 flex-1">
        <label class="label" for="q">Cari</label>
        <input id="q" class="field" name="q" value="{{ request('q') }}" placeholder="Kode unit, perkara, JPU">
    </div>
    <div class="w-56">
        <label class="label" for="status">Status</label>
        <select id="status" class="field" name="status">
            <option value="">Semua status</option>
            <option value="active" @selected(request('status') === 'active')>Sedang dipinjam</option>
            <option value="returned" @selected(request('status') === 'returned')>Sudah dikembalikan</option>
        </select>
    </div>
    <button class="btn-outline">Filter</button>
    <a class="btn-gold" href="{{ route('loans.create') }}">+ Peminjaman BB</a>
</form>

<div class="space-y-4">
    @forelse ($loans as $loan)
        <article class="card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a class="font-serif text-xl text-navy-900 hover:text-gold-600" href="{{ route('loans.show', $loan) }}">{{ $loan->physicalUnit?->unit_code }}</a>
                    <p class="mt-1 break-words text-sm font-medium text-navy-800">{{ $loan->physicalUnit?->legalCase?->case_number }}</p>
                    <p class="text-sm text-navy-500">{{ $loan->physicalUnit?->legalCase?->defendant_name }}</p>
                    <p class="mt-2 text-sm text-navy-700">Peminjam: <span class="font-semibold">{{ $loan->borrower_name }}</span></p>
                    @if($loan->court_date)
                        <p class="text-sm text-navy-500">Sidang {{ $loan->court_date->format('d/m/Y') }}</p>
                    @endif
                </div>
                @if($loan->isActive())
                    <span class="badge-warning">Dipinjam</span>
                @else
                    <span class="badge-success">Sudah dikembalikan</span>
                @endif
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="flex items-start gap-3 rounded-xl bg-navy-50/70 p-3">
                    @if($loan->hasLoanPhoto())
                        <a href="{{ $loan->loanPhotoUrl() }}" target="_blank">
                            <img class="thumb ring-1 ring-navy-100" src="{{ $loan->loanPhotoUrl() }}" alt="Foto saat dipinjam" width="64" height="64">
                        </a>
                    @else
                        <div class="thumb bg-white text-[10px] font-semibold leading-[64px] text-center text-navy-400 ring-1 ring-navy-100">—</div>
                    @endif
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Saat dipinjam</p>
                        <p class="mt-1 font-semibold">{{ $loan->loaned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl bg-navy-50/70 p-3">
                    @if($loan->hasReturnPhoto())
                        <a href="{{ $loan->returnPhotoUrl() }}" target="_blank">
                            <img class="thumb ring-1 ring-navy-100" src="{{ $loan->returnPhotoUrl() }}" alt="Foto saat dikembalikan" width="64" height="64">
                        </a>
                    @else
                        <div class="thumb bg-white text-[10px] font-semibold leading-[64px] text-center text-navy-400 ring-1 ring-navy-100">—</div>
                    @endif
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-navy-500">Saat dikembalikan</p>
                        @if($loan->isActive())
                            <p class="mt-1 font-semibold text-navy-500">Belum dikembalikan</p>
                        @else
                            <p class="mt-1 font-semibold">{{ $loan->returned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="card py-12 text-center text-navy-500">Belum ada peminjaman BB.</div>
    @endforelse
</div>

<div class="mt-4">{{ $loans->links() }}</div>
@endsection
