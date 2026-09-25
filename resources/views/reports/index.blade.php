@extends('layouts.app')

@section('title', 'Register & Laporan')
@section('heading', 'Register & Pelaporan')
@section('subheading', 'Form B-4 dan register peminjaman — PDF / Excel')

@section('content')
<div class="card max-w-3xl">
    <form class="grid gap-4 md:grid-cols-2" method="GET" action="{{ route('reports.b4.pdf') }}">
        <div class="md:col-span-2">
            <label class="label">Pencarian</label>
            <input class="field" name="q" value="{{ request('q') }}" placeholder="Token / no. reg / terdakwa">
        </div>
        <div>
            <label class="label">Dari tanggal</label>
            <input class="field" type="date" name="from" value="{{ request('from') }}">
        </div>
        <div>
            <label class="label">Sampai tanggal</label>
            <input class="field" type="date" name="to" value="{{ request('to') }}">
        </div>
        <div class="md:col-span-2 flex flex-wrap gap-2">
            <button class="btn-primary" formaction="{{ route('reports.b4.pdf') }}">PDF Form B-4</button>
            <button class="btn-outline" formaction="{{ route('reports.b4.excel') }}">Excel Form B-4</button>
            <button class="btn-primary" formaction="{{ route('reports.loans.pdf') }}">PDF Peminjaman</button>
            <button class="btn-outline" formaction="{{ route('reports.loans.excel') }}">Excel Peminjaman</button>
        </div>
    </form>
</div>
@endsection
