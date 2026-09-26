@extends('layouts.app')

@section('title', 'Tambah Akses Telegram')
@section('heading', 'Tambah Akses Telegram')
@section('subheading', 'Petugas yang boleh memakai bot SIBATA-BB')

@section('content')
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('whitelist.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @include('whitelist._form')
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('whitelist.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
