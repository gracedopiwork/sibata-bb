@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('heading', 'Tambah Pengguna')
@section('subheading', 'Admin masuk dashboard. Petugas PB3R hanya memakai bot Telegram.')

@section('content')
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('users.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @include('users._form')
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('users.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
