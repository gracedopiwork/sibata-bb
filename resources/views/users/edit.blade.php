@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('heading', 'Ubah '.$user->name)
@section('subheading', 'Kode lisensi untuk mengaktifkan bot Telegram, bukan untuk login portal.')

@section('content')
<div class="card mb-4 max-w-2xl">
    <p class="text-xs font-bold uppercase tracking-wider text-navy-500">Lisensi bot Telegram</p>
    <p class="mt-2 font-mono text-lg font-semibold tracking-wider">{{ $user->license_key }}</p>
    <p class="mt-1 text-sm text-navy-600">
        Status:
        <span class="{{ $user->hasValidLicense() ? 'badge-gold' : 'badge-danger' }}">{{ $user->hasValidLicense() ? 'Berlaku' : 'Dicabut' }}</span>
        @if ($user->license_issued_at)
            · terbit {{ $user->license_issued_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
        @endif
    </p>
    <div class="mt-4 flex flex-wrap gap-2">
        <form method="POST" action="{{ route('users.license.regenerate', $user) }}" onsubmit="return confirm('Terbitkan lisensi baru? Kode lama tidak berlaku.')">
            @csrf
            <button class="btn-gold">Terbitkan ulang</button>
        </form>
        @if ($user->id !== auth()->id())
            @if ($user->hasValidLicense())
                <form method="POST" action="{{ route('users.license.revoke', $user) }}" onsubmit="return confirm('Cabut lisensi pengguna ini?')">
                    @csrf
                    <button class="btn-danger">Cabut lisensi</button>
                </form>
            @else
                <form method="POST" action="{{ route('users.license.restore', $user) }}">
                    @csrf
                    <button class="btn-outline">Aktifkan kembali</button>
                </form>
            @endif
        @endif
    </div>
</div>
<div class="card max-w-2xl">
    <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-4 md:grid-cols-2">
        @csrf
        @method('PUT')
        @include('users._form', ['user' => $user])
        <div class="md:col-span-2 flex flex-wrap gap-2">
            <button class="btn-primary">Simpan</button>
            <a class="btn-outline" href="{{ route('users.index') }}">Batal</a>
        </div>
    </form>
    @if($user->id !== auth()->id())
        <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-6" onsubmit="return confirm('Hapus pengguna ini?')">
            @csrf
            @method('DELETE')
            <button class="btn-danger">Hapus pengguna</button>
        </form>
    @endif
</div>
@endsection
