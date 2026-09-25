@extends('layouts.app')

@section('title', 'Pengguna & Lisensi')
@section('heading', 'Pengguna & Lisensi')
@section('subheading', 'Setiap akun yang dibuat admin mendapat kode lisensi. Tanpa kode itu, portal tidak bisa dimasuki.')

@section('content')
@if (session('issued_license'))
    <div class="mb-4 rounded-2xl border border-gold-400/40 bg-gold-400/10 px-5 py-4">
        <p class="text-xs font-bold uppercase tracking-wider text-gold-700">Kode lisensi baru</p>
        <p class="mt-1 text-sm text-navy-800">Berikan kode ini kepada <b>{{ session('issued_license_user') }}</b>. Kode diperlukan saat masuk dashboard.</p>
        <p class="mt-3 font-mono text-lg font-semibold tracking-wider text-navy-900">{{ session('issued_license') }}</p>
    </div>
@endif

<div class="mb-4">
    <a class="btn-gold" href="{{ route('users.create') }}">Tambah pengguna + lisensi</a>
</div>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Lisensi</th>
                <th class="px-4 py-3">Peran</th>
                <th class="px-4 py-3">Akun</th>
                <th class="px-4 py-3">Lisensi</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3 font-mono text-xs tracking-wide">{{ $user->license_key ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $user->role->label() }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="{{ $user->hasValidLicense() ? 'badge-gold' : 'badge-danger' }}">{{ $user->hasValidLicense() ? 'Berlaku' : 'Dicabut' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-semibold text-navy-800" href="{{ route('users.edit', $user) }}">Ubah</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $users->links() }}</div>
</div>
@endsection
