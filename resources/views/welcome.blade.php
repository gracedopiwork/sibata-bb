<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIBATA-BB — Kejaksaan Negeri Wajo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sitaba-bb-mark.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|source-serif-4:600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-navy-900 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(201,162,39,0.16),transparent_40%)]"></div>
    <div class="relative mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-16">
        <img src="{{ asset('images/logo-sitaba-bb.png') }}" alt="Logo SIBATA-BB" class="h-32 w-32 rounded-full bg-white object-cover ring-1 ring-gold-500/40">
        <p class="mt-6 text-xs font-bold uppercase tracking-[0.3em] text-gold-400">Seksi PB3R · Kejaksaan Negeri Wajo</p>
        <h1 class="mt-4 font-serif text-5xl">SIBATA-BB</h1>
        <p class="mt-4 max-w-2xl text-lg text-navy-100/80">Sistem Informasi Barang Bukti dan Barang Rampasan. Pencatatan lapangan lewat bot Telegram, administrasi desktop untuk cetak label QR, laporan, dan analitik gudang PB3R.</p>
        <div class="mt-10 flex flex-wrap gap-3">
            @auth
                <a class="btn-gold" href="{{ route('dashboard') }}">Buka Dashboard</a>
            @else
                <a class="btn-gold" href="{{ route('login') }}">Masuk Dashboard</a>
            @endauth
        </div>
        <div class="mt-14 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-gold-400 text-sm font-bold">01</p>
                <h3 class="mt-2 font-semibold">Wadah fisik</h3>
                <p class="mt-1 text-sm text-navy-100/70">Stiker BB-YYYY-XXX atau PKT-YYYY-XXX menempel pada satuan atau paket tersegel.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-gold-400 text-sm font-bold">02</p>
                <h3 class="mt-2 font-semibold">Bot Telegram</h3>
                <p class="mt-1 text-sm text-navy-100/70">Wizard /tambah, pinjam sidang, kembali gudang, dan eksekusi putusan per item.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <p class="text-gold-400 text-sm font-bold">03</p>
                <h3 class="mt-2 font-semibold">Portal web</h3>
                <p class="mt-1 text-sm text-navy-100/70">Cetak label, antrean stiker, dan ekspor register Excel PB3R.</p>
            </div>
        </div>
    </div>
</body>
</html>
