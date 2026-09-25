@extends('layouts.app')

@section('title', 'Ubah Jenis BB')
@section('heading', 'Ubah Jenis BB')
@section('subheading', $category->name)

@section('content')
<div class="card max-w-xl">
    <form method="POST" action="{{ route('evidence-categories.update', $category) }}" class="space-y-3">
        @csrf
        @method('PUT')
        <div>
            <label class="label">Nama</label>
            <input class="field" name="name" value="{{ old('name', $category->name) }}" required>
        </div>
        <div>
            <label class="label">Kode</label>
            <input class="field font-mono uppercase" name="code" value="{{ old('code', $category->code) }}" required>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
            Aktif
        </label>
        <div class="flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('evidence-categories.index') }}">Batal</a>
        </div>
    </form>
    <form method="POST" action="{{ route('evidence-categories.destroy', $category) }}" class="mt-6" onsubmit="return confirm('Hapus jenis BB ini?')">
        @csrf
        @method('DELETE')
        <button class="btn-danger">Hapus</button>
    </form>
</div>
@endsection
