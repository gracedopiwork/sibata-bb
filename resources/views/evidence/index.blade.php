@extends('layouts.app')

@section('title', 'Barang Bukti')
@section('heading', 'Register Barang Bukti')
@section('subheading', 'CRUD gudang, stiker QR, dan status BB')

@section('content')
<div class="card">
    <form class="grid gap-3 md:grid-cols-4" method="GET">
        <input class="field md:col-span-2" type="search" name="q" value="{{ request('q') }}" placeholder="Cari token, no. reg, terdakwa, barang...">
        <select class="field" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a class="btn-outline" href="{{ route('evidence.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="mt-4 flex flex-wrap gap-2">
    @if(auth()->user()->canManageEvidence())
        <a class="btn-gold" href="{{ route('evidence.create') }}">Daftar BB baru</a>
        <a class="btn-outline" href="{{ route('evidence.import') }}">Impor Excel</a>
        <a class="btn-outline" href="{{ route('evidence.labels.bulk') }}">Cetak stiker massal</a>
    @endif
</div>

<div class="card mt-4 overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Token QR</th>
                <th class="px-4 py-3">No. Reg BB</th>
                <th class="px-4 py-3">Terdakwa</th>
                <th class="px-4 py-3">Barang</th>
                <th class="px-4 py-3">Rak</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @forelse ($items as $item)
                <tr class="hover:bg-navy-50/60">
                    <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $item->qr_token }}</td>
                    <td class="px-4 py-3">{{ $item->no_reg_bb }}</td>
                    <td class="px-4 py-3">{{ $item->nama_terdakwa }}</td>
                    <td class="px-4 py-3 max-w-xs truncate">{{ $item->nama_barang }}</td>
                    <td class="px-4 py-3">{{ $item->lokasi_rak }}</td>
                    <td class="px-4 py-3"><span class="{{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-semibold text-navy-800 hover:text-gold-600" href="{{ route('evidence.show', $item) }}">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-navy-500">Belum ada barang bukti.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $items->links() }}</div>
</div>
@endsection
