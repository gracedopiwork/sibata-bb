@extends('layouts.app')

@section('title', 'Data JPU')
@section('heading', 'Data Master JPU')
@section('subheading', 'Daftar jaksa yang bisa dipilih saat mencatat perkara')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card">
        <h3 class="font-serif text-lg">Tambah JPU</h3>
        <form method="POST" action="{{ route('prosecutors.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="label">Nama</label>
                <input class="field" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="label">NIP (opsional)</label>
                <input class="field" name="nip" value="{{ old('nip') }}">
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
                    <th class="px-4 py-3">NIP</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-100">
                @foreach ($prosecutors as $prosecutor)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $prosecutor->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $prosecutor->nip ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $prosecutor->is_active ? 'badge-success' : 'badge-danger' }}">{{ $prosecutor->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-semibold text-navy-800" href="{{ route('prosecutors.edit', $prosecutor) }}">Ubah</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-4 py-3">{{ $prosecutors->links() }}</div>
    </div>
</div>
@endsection
