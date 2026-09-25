@extends('layouts.app')

@section('title', 'Peminjaman BB')
@section('heading', 'Catat Peminjaman BB')
@section('subheading', 'Foto kondisi saat keluar gudang wajib dilampirkan')

@section('content')
<form method="POST" action="{{ route('loans.store') }}" enctype="multipart/form-data" class="card max-w-3xl space-y-4">
    @csrf
    <div>
        <label class="label">Unit barang bukti</label>
        <select class="field" name="physical_unit_id" required>
            <option value="">Pilih unit tersimpan gudang</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('physical_unit_id') == $unit->id)>
                    {{ $unit->unit_code }} · {{ $unit->legalCase?->case_number }} · {{ $unit->legalCase?->defendant_name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">JPU peminjam</label>
        <div class="grid gap-2 rounded-xl border border-navy-100 p-3 md:grid-cols-2">
            @forelse ($prosecutors as $prosecutor)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="prosecutor_ids[]" value="{{ $prosecutor->id }}" @checked(in_array($prosecutor->id, old('prosecutor_ids', [])))>
                    <span>{{ $prosecutor->name }}</span>
                </label>
            @empty
                <p class="text-sm text-navy-500 md:col-span-2">Belum ada JPU di data master.</p>
            @endforelse
        </div>
    </div>
    <div>
        <label class="label">Tanggal sidang</label>
        <input class="field" type="date" name="court_date" value="{{ old('court_date') }}" required>
    </div>
    <div>
        <label class="label">Catatan</label>
        <textarea class="field" name="notes" rows="2">{{ old('notes') }}</textarea>
    </div>
    <div>
        <label class="label">Foto saat dipinjam</label>
        <input class="field" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
        <p class="mt-1 text-xs text-navy-500">JPG, PNG, WEBP, atau GIF. Maksimal 12 MB.</p>
    </div>
    <div class="flex gap-2">
        <button class="btn-gold">Simpan peminjaman</button>
        <a class="btn-outline" href="{{ route('loans.index') }}">Batal</a>
    </div>
</form>
@endsection
