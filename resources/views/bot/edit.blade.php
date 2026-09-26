@extends('layouts.app')

@section('title', 'Bot Telegram')
@section('heading', 'Bot Telegram SIBATA-BB')
@section('subheading', 'Buat bot di BotFather, lalu tempel token di sini')

@section('content')
<div class="grid gap-6 xl:grid-cols-2">
    <div class="card">
        <h3 class="font-serif text-lg text-navy-900">1. Buat bot di Telegram</h3>
        <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-navy-800">
            <li>Buka aplikasi Telegram di HP atau komputer.</li>
            <li>Cari akun <b>@BotFather</b> (centang biru).</li>
            <li>Ketik <code class="font-mono text-xs">/newbot</code>.</li>
            <li>Nama tampilan: <b>SIBATA-BB Kejari Wajo</b>.</li>
            <li>Username: misalnya <b>SibataWajoBot</b> (wajib berakhiran <code class="font-mono text-xs">bot</code>).</li>
            <li>Salin token yang diberikan BotFather (bentuknya <code class="font-mono text-xs">123456:AAHxxxx</code>).</li>
        </ol>
    </div>

    <div class="card">
        <h3 class="font-serif text-lg text-navy-900">2. Tempel token</h3>
        @if ($profile)
            <p class="mt-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                Tersambung: <b>{{ $profile['first_name'] ?? 'SIBATA-BB' }}</b>
                @if (!empty($profile['username']))
                    — {{ '@'.$profile['username'] }}
                @endif
            </p>
        @elseif ($hasToken)
            <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">Token sudah tersimpan, tetapi belum bisa dihubungi Telegram. Periksa token atau koneksi internet.</p>
        @endif

        <form method="POST" action="{{ route('bot.update') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="label" for="bot_token">Token BotFather</label>
                <input class="field font-mono text-sm" id="bot_token" name="bot_token" type="password" autocomplete="off" placeholder="123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
            </div>
            <button class="btn-primary" type="submit">Simpan & aktifkan bot</button>
        </form>
    </div>

    <div class="card xl:col-span-2">
        <h3 class="font-serif text-lg text-navy-900">3. Hidupkan bot di komputer ini</h3>
        <p class="mt-3 text-sm text-navy-700">Karena aplikasi masih di localhost, bot memakai long polling (tanpa webhook HTTPS). Buka terminal di folder proyek, lalu jalankan:</p>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-navy-900 px-4 py-3 text-sm text-gold-300">php artisan telegram:poll</pre>
        <p class="mt-3 text-sm text-navy-700">Biarkan jendela itu tetap terbuka. Di Telegram, cari bot Anda, ketik <b>/start</b>. Pengguna pertama otomatis menjadi Admin PB3R dan masuk daftar putih.</p>
    </div>
</div>
@endsection
