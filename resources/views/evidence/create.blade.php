@extends('layouts.app')

@section('title', 'Daftar BB')
@section('heading', 'Daftar Barang Bukti')
@section('subheading', 'Token QR BB-WAJO-YYYY-XXXX akan dibuat otomatis')

@section('content')
<div class="card max-w-3xl">
    <form method="POST" action="{{ route('evidence.store') }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @include('evidence._form')
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('evidence.index') }}">Batal</a>
        </div>
    </form>
</div>
@endsection
