@extends('layouts.app')

@section('title', 'Peminjaman '.$loan->physicalUnit?->unit_code)
@section('heading', $loan->physicalUnit?->unit_code)
@section('subheading', 'Peminjaman BB · '.$loan->physicalUnit?->legalCase?->case_number)

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn-outline" href="{{ route('loans.index') }}">Daftar peminjaman</a>
    @if($loan->physicalUnit)
        <a class="btn-outline" href="{{ route('units.show', $loan->physicalUnit) }}">Buka unit</a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-navy-500">Terdakwa</p>
                <p class="font-semibold">{{ $loan->physicalUnit?->legalCase?->defendant_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Peminjam</p>
                <p class="font-semibold">{{ $loan->borrower_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Tanggal sidang</p>
                <p class="font-semibold">{{ $loan->court_date?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Status</p>
                @if($loan->isActive())
                    <span class="badge-warning">Dipinjam</span>
                @else
                    <span class="badge-success">Sudah kembali</span>
                @endif
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Dipinjam</p>
                <p class="font-semibold">{{ $loan->loaned_by }} · {{ $loan->loaned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
            </div>
            @if(! $loan->isActive())
                <div>
                    <p class="text-xs uppercase text-navy-500">Dikembalikan</p>
                    <p class="font-semibold">{{ $loan->returned_by }} · {{ $loan->returned_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                    <p class="text-sm text-navy-600">{{ $loan->returnStorageLocation?->name ?? $loan->return_storage_location }}</p>
                </div>
            @endif
            @if($loan->notes)
                <div class="md:col-span-2">
                    <p class="text-xs uppercase text-navy-500">Catatan pinjam</p>
                    <p>{{ $loan->notes }}</p>
                </div>
            @endif
            @if($loan->return_notes)
                <div class="md:col-span-2">
                    <p class="text-xs uppercase text-navy-500">Catatan kembali</p>
                    <p>{{ $loan->return_notes }}</p>
                </div>
            @endif
        </div>

        <div class="card grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-navy-500">Foto saat dipinjam</p>
                @if($loan->hasLoanPhoto())
                    <img class="mt-2 max-h-72 w-full rounded-xl object-cover" src="{{ $loan->loanPhotoUrl() }}" alt="Foto pinjam">
                @else
                    <p class="mt-2 text-sm text-navy-500">Tidak ada foto pinjam.</p>
                @endif
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Foto saat dikembalikan</p>
                @if($loan->hasReturnPhoto())
                    <img class="mt-2 max-h-72 w-full rounded-xl object-cover" src="{{ $loan->returnPhotoUrl() }}" alt="Foto kembali">
                @else
                    <p class="mt-2 text-sm text-navy-500">Belum ada foto kembali.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @if($loan->isActive())
            <form class="card space-y-3" method="POST" action="{{ route('loans.return', $loan) }}" enctype="multipart/form-data">
                @csrf
                <h3 class="font-serif text-lg">Kembalikan ke gudang</h3>
                <select class="field" name="storage_location_id" required>
                    @foreach ($storageLocations as $location)
                        <option value="{{ $location->id }}" @selected((int) $loan->physicalUnit?->storage_location_id === (int) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <textarea class="field" name="notes" rows="2" placeholder="Kondisi fisik saat kembali"></textarea>
                <div>
                    <label class="label">Foto saat dikembalikan</label>
                    <input class="field" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
                </div>
                <button class="btn-primary w-full">Catat pengembalian</button>
            </form>
        @endif
    </div>
</div>
@endsection
