@extends('layouts.app')

@section('title', 'Ubah Tempat Penyimpanan')
@section('heading', 'Ubah Tempat Penyimpanan')
@section('subheading', $location->name)

@section('content')
<div class="card max-w-xl">
    <form method="POST" action="{{ route('storage-locations.update', $location) }}" class="space-y-3">
        @csrf
        @method('PUT')
        <div>
            <label class="label">Nama lokasi</label>
            <input class="field" name="name" value="{{ old('name', $location->name) }}" required>
        </div>
        <div>
            <label class="label">Kode</label>
            <input class="field font-mono uppercase" name="code" value="{{ old('code', $location->code) }}" required>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $location->is_active))>
            Aktif
        </label>
        <div class="flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('storage-locations.index') }}">Batal</a>
        </div>
    </form>
    @if (! $location->physicalUnits()->exists())
        <form method="POST" action="{{ route('storage-locations.destroy', $location) }}" class="mt-6" onsubmit="return confirm('Hapus tempat penyimpanan ini?')">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Hapus</button>
        </form>
    @endif
</div>
@endsection
