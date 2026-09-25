@extends('layouts.app')

@section('title', $unit->unit_code)
@section('heading', $unit->unit_code)
@section('subheading', $unit->unit_type->label().' · '.$unit->legalCase?->case_number)

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a class="btn-gold" href="{{ route('print-labels.sheet', ['ids' => $unit->id]) }}">{{ $unit->is_printed ? 'Cetak ulang label' : 'Cetak label' }}</a>
    <a class="btn-outline" href="{{ $unit->publicViewUrl() }}" target="_blank">Pratinjau pindai QR</a>
    @if($unit->legalCase)
        <a class="btn-outline" href="{{ route('cases.show', $unit->legalCase) }}">Buka perkara</a>
    @endif
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs uppercase text-navy-500">Terdakwa</p>
                <p class="font-semibold">{{ $unit->legalCase?->defendant_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">JPU</p>
                <p class="font-semibold">{{ $unit->legalCase?->prosecutor_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Jenis aset</p>
                <p class="font-semibold">{{ $unit->assetType?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Lokasi</p>
                <p class="font-semibold">{{ $unit->storageLocation?->name ?? $unit->storage_location }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-navy-500">Status</p>
                <span class="{{ $unit->current_status->badgeClass() }}">{{ $unit->current_status->label() }}</span>
            </div>
            <div class="md:col-span-2 space-y-3">
                @if($unit->hasPhoto())
                    <img class="max-h-64 rounded-xl object-cover" src="{{ $unit->photoUrl() }}" alt="Foto unit">
                @else
                    <p class="text-sm text-navy-500">Belum ada foto unit.</p>
                @endif
                <form method="POST" action="{{ route('units.photo.update', $unit) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2">
                    @csrf
                    <div class="min-w-56 flex-1">
                        <label class="label">{{ $unit->hasPhoto() ? 'Ganti foto' : 'Unggah foto' }}</label>
                        <input class="field" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    </div>
                    <button class="btn-outline">Simpan foto</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3 class="font-serif text-lg">Rincian isi</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase text-navy-500">
                        <tr>
                            <th class="pb-2">Item</th>
                            <th class="pb-2">Kategori</th>
                            <th class="pb-2">Jumlah</th>
                            <th class="pb-2">Putusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-navy-100">
                        @foreach ($unit->items as $item)
                            <tr>
                                <td class="py-2">{{ $item->item_name }}</td>
                                <td class="py-2">{{ $item->categoryLabel() }}</td>
                                <td class="py-2">{{ $item->quantity }}</td>
                                <td class="py-2">{{ $item->verdict_status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($unit->unit_type->value === 'PACK')
                <form class="mt-6 space-y-2" method="POST" action="{{ route('units.children.bulk', $unit) }}">
                    @csrf
                    <label class="label">Tempel daftar isi (satu baris per barang)</label>
                    <textarea class="field font-mono text-sm" name="contents_bulk" rows="5" placeholder="2 sachet sabu 0,5 gram&#10;1 unit timbangan digital | ELEKTRONIK&#10;HP Vivo Y21 | ELEKTRONIK | 1 unit" required></textarea>
                    <p class="text-xs text-navy-500">Format: nama | kategori | jumlah. Segel tidak perlu dibuka.</p>
                    <button class="btn-gold">Tambah semua isi</button>
                </form>
                <form class="mt-4 grid gap-3 md:grid-cols-4" method="POST" action="{{ route('units.children.store', $unit) }}">
                    @csrf
                    <input class="field md:col-span-2" name="item_name" placeholder="Atau tambah satu item" required>
                    <select class="field" name="category" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->code }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input class="field" name="quantity" value="1" required>
                    <button class="btn-outline md:col-span-4">Tambah satu isi</button>
                </form>
            @endif
        </div>

        <div class="card">
            <h3 class="font-serif text-lg">Eksekusi putusan per item</h3>
            @foreach ($unit->items as $item)
                @if($item->verdict_status->isFinal())
                    <p class="mt-3 text-sm text-navy-600">{{ $item->item_name }} — {{ $item->verdict_status->label() }}
                        @if($item->execution_ba_number) (BA {{ $item->execution_ba_number }}) @endif
                    </p>
                @else
                    <form class="mt-4 space-y-3 rounded-xl border border-navy-100 p-4" method="POST" action="{{ route('units.items.execute', [$unit, $item]) }}" enctype="multipart/form-data">
                        @csrf
                        <p class="font-semibold">{{ $item->item_name }}</p>
                        <div class="grid gap-3 md:grid-cols-2">
                            <select class="field" name="verdict_status" required>
                                @foreach ($verdicts as $verdict)
                                    <option value="{{ $verdict->value }}">{{ $verdict->label() }}</option>
                                @endforeach
                            </select>
                            <input class="field" name="execution_ba_number" placeholder="Nomor BA eksekusi">
                            <input class="field" name="execution_recipient" placeholder="Penerima (jika dikembalikan)">
                            <input class="field" name="execution_recipient_nik" placeholder="NIK penerima">
                            <input class="field md:col-span-2" type="file" name="execution_proof_photo" accept="image/*">
                        </div>
                        <button class="btn-primary">Simpan eksekusi</button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>

    <div class="space-y-6">
        @if($unit->current_status->value === 'TERSIMPAN_GUDANG')
            <form class="card space-y-3" method="POST" action="{{ route('units.loan', $unit) }}">
                @csrf
                <h3 class="font-serif text-lg">Pinjam sidang</h3>
                <input class="field" name="borrower_name" placeholder="Nama JPU" required>
                <input class="field" type="date" name="court_date" required>
                <textarea class="field" name="notes" rows="2" placeholder="Catatan"></textarea>
                <button class="btn-gold w-full">Catat peminjaman</button>
            </form>
        @endif

        @if($unit->current_status->value === 'DIPINJAM_SIDANG')
            <form class="card space-y-3" method="POST" action="{{ route('units.return', $unit) }}">
                @csrf
                <h3 class="font-serif text-lg">Kembali gudang</h3>
                <select class="field" name="storage_location_id" required>
                    @foreach ($storageLocations as $location)
                        <option value="{{ $location->id }}" @selected((int) $unit->storage_location_id === (int) $location->id || $unit->storage_location === $location->name)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <textarea class="field" name="notes" rows="2" placeholder="Kondisi fisik"></textarea>
                <button class="btn-primary w-full">Catat pengembalian</button>
            </form>
        @endif

        <div class="card">
            <h3 class="font-serif text-lg">Riwayat mutasi</h3>
            <ul class="mt-3 space-y-3 text-sm">
                @forelse ($unit->mutations as $mutation)
                    <li>
                        <p class="font-semibold">{{ $mutation->mutation_type->label() }}</p>
                        <p class="text-navy-600">{{ $mutation->handled_by }} · {{ $mutation->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                        @if($mutation->borrower_name)
                            <p>{{ $mutation->borrower_name }} @if($mutation->court_date) · sidang {{ $mutation->court_date->format('d/m/Y') }} @endif</p>
                        @endif
                        @if($mutation->notes)
                            <p class="text-navy-500">{{ $mutation->notes }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-navy-500">Belum ada mutasi.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
