@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="relative min-h-screen overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(201,162,39,0.18),transparent_32%),linear-gradient(160deg,#07111f,#0b1f3a_46%,#122a4a)]"></div>
    <div class="relative mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-6 py-12 lg:grid-cols-2">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.28em] text-gold-400">Kejaksaan Negeri Wajo</p>
            <h1 class="mt-3 font-serif text-4xl leading-tight text-white md:text-5xl">SITABA-BB</h1>
            <p class="mt-4 max-w-md text-navy-100/80">Sistem Informasi Tata Kelola Barang Bukti — dual akses Telegram (lapangan) dan portal web (administrasi) untuk Seksi PB3R Kejaksaan Negeri Wajo.</p>
            <ul class="mt-8 space-y-2 text-sm text-navy-100/70">
                <li>• Unit mandiri (BB) dan paket/wadah (PKT) dengan satu stiker QR</li>
                <li>• Peminjaman sidang, pengembalian, dan eksekusi putusan</li>
                <li>• Cetak label QR 70×50 mm dan ekspor register Excel</li>
            </ul>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white p-8 text-navy-900 shadow-2xl">
            <h2 class="font-serif text-2xl">Masuk Dashboard</h2>
            <p class="mt-1 text-sm text-navy-600">Hanya administrator berlisensi. Akun dan kode lisensi diterbitkan dari menu Pengguna. Petugas gudang memakai bot Telegram.</p>
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" class="field" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div>
                    <label class="label" for="password">Kata sandi</label>
                    <input id="password" class="field" type="password" name="password" required>
                </div>
                <div>
                    <label class="label" for="license_key">Kode lisensi</label>
                    <input id="license_key" class="field font-mono uppercase tracking-wider" type="text" name="license_key" value="{{ old('license_key') }}" required autocomplete="off" placeholder="SITABA-XXXX-XXXX-XXXX">
                    <p class="mt-1 text-xs text-navy-500">Kode ini dibuat otomatis saat admin menambah pengguna.</p>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" class="rounded border-navy-300"> Ingat saya
                </label>
                @if ($errors->any())
                    <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                @endif
                <button class="btn-primary w-full">Masuk</button>
            </form>
        </div>
    </div>
</div>
@endsection
