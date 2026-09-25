@extends('layouts.app')

@section('title', 'Ubah Akses Telegram')
@section('heading', 'Ubah Akses Telegram')
@section('subheading', $entry->user_name)

@section('content')
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('whitelist.update', $entry) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        @include('whitelist._form', ['entry' => $entry])
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('whitelist.index') }}">Batal</a>
        </div>
    </form>
    <form class="mt-6" method="POST" action="{{ route('whitelist.destroy', $entry) }}" onsubmit="return confirm('Hapus akses Telegram ini?')">
        @csrf
        @method('DELETE')
        <button class="btn-danger">Hapus</button>
    </form>
</div>
@endsection
