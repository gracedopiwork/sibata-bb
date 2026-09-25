@extends('layouts.app')

@section('title', 'Tempat Penyimpanan')
@section('heading', 'Data Master Tempat Penyimpanan')
@section('subheading', 'Lokasi gudang yang dipilih saat mencatat atau mengembalikan barang bukti')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card">
        <h3 class="font-serif text-lg">Tambah tempat penyimpanan</h3>
        <form method="POST" action="{{ route('storage-locations.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="label">Nama lokasi</label>
                <input class="field" name="name" value="{{ old('name') }}" placeholder="Brankas PB3R Laci 02" required>
            </div>
            <div>
                <label class="label">Kode (opsional)</label>
                <input class="field font-mono uppercase" name="code" value="{{ old('code') }}" placeholder="BRANKAS_02">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                Aktif
            </label>
            <button class="btn-gold w-full">Simpan</button>
        </form>
    </div>
    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <table class="min-w-full text-sm">
            <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-100">
                @foreach ($locations as $location)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $location->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $location->code }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $location->is_active ? 'badge-success' : 'badge-danger' }}">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-semibold text-navy-800" href="{{ route('storage-locations.edit', $location) }}">Ubah</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $locations->links() }}</div>
    </div>
</div>
@endsection
