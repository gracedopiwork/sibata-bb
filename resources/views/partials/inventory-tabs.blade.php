<nav class="mb-5 flex flex-wrap gap-2">
    <a href="{{ route('items.index') }}" class="{{ request()->routeIs('items.*') ? 'btn-gold' : 'btn-outline' }}">Barang bukti</a>
    <a href="{{ route('seals.index') }}" class="{{ request()->routeIs('seals.*') ? 'btn-gold' : 'btn-outline' }}">Segel</a>
    <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'btn-gold' : 'btn-outline' }}">Unit / QR</a>
    <a href="{{ route('print-labels.index') }}" class="{{ request()->routeIs('print-labels.*') ? 'btn-gold' : 'btn-outline' }}">Cetak label</a>
</nav>
