@extends('layouts.app')

@section('title', 'Akses Telegram')
@section('heading', 'Akses Telegram')
@section('subheading', 'Daftar putih petugas yang boleh memakai bot SITABA-BB')

@section('content')
@php
    $botReady = filled(config('services.telegram.bot_token'));
@endphp
<div class="mb-4 rounded-xl border px-4 py-3 text-sm {{ $botReady ? 'border-emerald-200 bg-emerald-50 text-navy-800' : 'border-amber-200 bg-amber-50 text-navy-800' }}">
    @if ($botReady)
        Bot Telegram sudah punya token. Untuk uji di komputer ini jalankan <code class="font-mono text-xs">php artisan telegram:poll</code>, lalu ketik /start di Telegram.
    @else
        Bot belum dibuat di Telegram. Buka <b>@BotFather</b> → /newbot, lalu jalankan <code class="font-mono text-xs">php artisan telegram:setup</code> dan tempel tokennya.
    @endif
</div>
<div class="mb-4">
    <a class="btn-gold" href="{{ route('whitelist.create') }}">Tambah akses</a>
</div>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full text-sm">
        <thead class="bg-navy-50 text-left text-xs uppercase tracking-wide text-navy-600">
            <tr>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Chat ID</th>
                <th class="px-4 py-3">Peran</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-navy-100">
            @foreach ($entries as $entry)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $entry->user_name }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $entry->telegram_chat_id }}</td>
                    <td class="px-4 py-3">{{ $entry->role->label() }}</td>
                    <td class="px-4 py-3">
                        <span class="{{ $entry->is_active ? 'badge-success' : 'badge-danger' }}">{{ $entry->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-semibold text-navy-800" href="{{ route('whitelist.edit', $entry) }}">Ubah</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-4 py-3">{{ $entries->links() }}</div>
</div>
@endsection
