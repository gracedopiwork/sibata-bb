<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SITABA-BB') — Kejari Wajo</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|source-serif-4:600,700" rel="stylesheet" />
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-parchment" x-data="{ sidebar: false }">
    <div class="lg:flex min-h-screen">
        <div x-show="sidebar" x-cloak class="fixed inset-0 z-30 bg-navy-950/50 lg:hidden" @click="sidebar = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-navy-900 text-white transition lg:static lg:translate-x-0"
               :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="border-b border-white/10 px-6 py-5">
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gold-400">Kejari Wajo · PB3R</p>
                <h1 class="font-serif text-2xl text-white">SITABA-BB</h1>
                <p class="mt-1 text-xs text-navy-100/70">Tata kelola barang bukti</p>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">Dashboard</a>
                <a href="{{ route('cases.index') }}" class="nav-link {{ request()->routeIs('cases.*') ? 'nav-link-active' : '' }}">Register Perkara</a>
                <p class="px-3 pt-4 text-[11px] font-bold uppercase tracking-wider text-navy-100/40">Data Master</p>
                <a href="{{ route('prosecutors.index') }}" class="nav-link {{ request()->routeIs('prosecutors.*') ? 'nav-link-active' : '' }}">JPU</a>
                <a href="{{ route('case-types.index') }}" class="nav-link {{ request()->routeIs('case-types.*') ? 'nav-link-active' : '' }}">Jenis Perkara</a>
                <a href="{{ route('asset-types.index') }}" class="nav-link {{ request()->routeIs('asset-types.*') ? 'nav-link-active' : '' }}">Jenis Aset</a>
                <a href="{{ route('evidence-categories.index') }}" class="nav-link {{ request()->routeIs('evidence-categories.*') ? 'nav-link-active' : '' }}">Jenis BB</a>
                <a href="{{ route('storage-locations.index') }}" class="nav-link {{ request()->routeIs('storage-locations.*') ? 'nav-link-active' : '' }}">Tempat Penyimpanan</a>
                <a href="{{ route('units.index') }}" class="nav-link {{ request()->routeIs('units.*') ? 'nav-link-active' : '' }}">Inventaris Fisik</a>
                <a href="{{ route('print-labels.index') }}" class="nav-link {{ request()->routeIs('print-labels.*') ? 'nav-link-active' : '' }}">Antrean Cetak Label</a>
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">Laporan / Ekspor</a>
                @if(auth()->user()->canManageUsers())
                    <a href="{{ route('bot.edit') }}" class="nav-link {{ request()->routeIs('bot.*') ? 'nav-link-active' : '' }}">Bot Telegram</a>
                    <a href="{{ route('whitelist.index') }}" class="nav-link {{ request()->routeIs('whitelist.*') ? 'nav-link-active' : '' }}">Akses Telegram</a>
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">Pengguna &amp; Lisensi</a>
                @endif
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gold-300">{{ auth()->user()->role->label() }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="btn-outline w-full border-white/20 bg-transparent text-white hover:bg-white/10">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-navy-100 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                <button class="rounded-lg p-2 text-navy-800 lg:hidden" @click="sidebar = true" type="button">☰</button>
                <div>
                    <h2 class="font-serif text-xl text-navy-900">@yield('heading', 'Dashboard')</h2>
                    <p class="text-xs text-navy-600">@yield('subheading', 'Seksi Pengelolaan Barang Bukti dan Barang Rampasan')</p>
                </div>
                <span class="hidden text-xs font-semibold uppercase tracking-wider text-navy-500 sm:inline">{{ now()->timezone(config('app.timezone'))->translatedFormat('l, d F Y') }}</span>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
