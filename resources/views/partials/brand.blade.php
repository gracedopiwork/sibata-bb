@php
    $size = $size ?? 48;
    $showText = $showText ?? true;
    $mark = $mark ?? false;
    $src = $mark ? asset('images/logo-sitaba-bb-mark.png') : asset('images/logo-sitaba-bb.png');
@endphp
<div class="flex items-center gap-3 {{ $class ?? '' }}">
    <img src="{{ $src }}" alt="Logo SIBATA-BB" width="{{ $size }}" height="{{ $size }}" class="shrink-0 rounded-full ring-1 ring-gold-500/40">
    @if ($showText)
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gold-400">Kejari Wajo · PB3R</p>
            <p class="font-serif text-2xl leading-none text-white">SIBATA-BB</p>
            @if ($subtitle ?? true)
                <p class="mt-1 text-xs text-navy-100/70">Sistem Informasi Barang Bukti dan Barang Rampasan</p>
            @endif
        </div>
    @endif
</div>
