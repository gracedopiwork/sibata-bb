@extends('layouts.app')

@section('title', 'Ubah Jenis Aset')
@section('heading', 'Ubah Jenis Aset')
@section('subheading', $type->name)

@section('content')
<div class="card max-w-xl">
    <form method="POST" action="{{ route('asset-types.update', $type) }}" class="space-y-3">
        @csrf
        @method('PUT')
        <div>
            <label class="label">Nama</label>
            <input class="field" name="name" value="{{ old('name', $type->name) }}" required>
        </div>
        <div>
            <label class="label">Kode</label>
            <input class="field font-mono uppercase" name="code" value="{{ old('code', $type->code) }}" required>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active))>
            Aktif
        </label>
        <div class="flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('asset-types.index') }}">Batal</a>
        </div>
    </form>
    @if (! $type->physicalUnits()->exists())
        <form method="POST" action="{{ route('asset-types.destroy', $type) }}" class="mt-6" onsubmit="return confirm('Hapus jenis aset ini?')">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Hapus</button>
        </form>
    @endif
</div>
@endsection
