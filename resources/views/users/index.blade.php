@extends('layouts.app')

@section('title', 'Pengguna')
@section('heading', 'Whitelist Pengguna')
@section('subheading', 'Admin untuk dashboard; petugas PB3R hanya untuk whitelist bot Telegram')

@section('content')
<div class="mb-4">
    <a class="btn-gold" href="{{ route('users.create') }}">Tambah pengguna</a>
</div>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">NIP</th>
                <th class="px-4 py-3">Telegram ID</th>
                <th class="px-4 py-3">Peran</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ $user->nip ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $user->telegram_id ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $user->role->label() }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
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
