@extends('layouts.app')

@section('title', 'Ubah BB')
@section('heading', 'Ubah '.$item->qr_token)
@section('subheading', 'Perubahan lokasi rak akan tercatat sebagai relokasi')

@section('content')
<div class="card max-w-3xl">
    <form method="POST" action="{{ route('evidence.update', $item) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        @include('evidence._form', ['item' => $item])
        <div class="md:col-span-2 flex gap-2">
            <button class="btn-primary">Simpan perubahan</button>
            <a class="btn-outline" href="{{ route('evidence.show', $item) }}">Batal</a>
        </div>
    </form>
</div>
@endsection
