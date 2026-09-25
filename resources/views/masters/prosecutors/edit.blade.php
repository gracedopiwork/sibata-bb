@extends('layouts.app')

@section('title', 'Ubah JPU')
@section('heading', 'Ubah JPU')
@section('subheading', $prosecutor->name)

@section('content')
<div class="card max-w-xl">
    <form method="POST" action="{{ route('prosecutors.update', $prosecutor) }}" class="space-y-3">
        @csrf
        @method('PUT')
        <div>
            <label class="label">Nama</label>
            <input class="field" name="name" value="{{ old('name', $prosecutor->name) }}" required>
        </div>
        <div>
            <label class="label">NIP</label>
            <input class="field" name="nip" value="{{ old('nip', $prosecutor->nip) }}">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $prosecutor->is_active))>
            Aktif
        </label>
        <div class="flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('prosecutors.index') }}">Batal</a>
        </div>
    </form>
    @if (! $prosecutor->cases()->exists())
        <form method="POST" action="{{ route('prosecutors.destroy', $prosecutor) }}" class="mt-6" onsubmit="return confirm('Hapus JPU ini?')">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Hapus</button>
        </form>
    @endif
</div>
@endsection
