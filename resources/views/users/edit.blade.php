@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('heading', 'Ubah '.$user->name)
@section('subheading', 'Admin masuk dashboard. Petugas PB3R hanya memakai bot Telegram.')

@section('content')
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user])
        <div class="md:col-span-2 flex flex-wrap gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('users.index') }}">Batal</a>
        </div>
    </form>
    @if($user->id !== auth()->id())
        <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-6" onsubmit="return confirm('Hapus pengguna ini?')">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Hapus pengguna</button>
        </form>
    @endif
</div>
@endsection
