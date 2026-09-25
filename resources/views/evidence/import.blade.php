@extends('layouts.app')

@section('title', 'Impor Gudang')
@section('heading', 'Impor Massal Barang Bukti')
@section('subheading', 'Unggah .xlsx / .csv gudang awal. Token QR dibuat otomatis.')

@section('content')
<div class="card max-w-2xl">
    <p class="text-sm text-navy-700">Kolom: <code>no_reg_bb, no_reg_perkara, nama_terdakwa, nama_barang, jumlah_satuan, lokasi_rak, status</code>. Baris dengan No. Reg BB yang sudah ada akan dilewati.</p>
    <div class="mt-4 flex gap-2">
        <a class="btn-outline" href="{{ route('evidence.import.template') }}">Unduh template CSV</a>
    </div>
    <form method="POST" action="{{ route('evidence.import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
        @csrf
        <div>
            <label class="label">Berkas Excel / CSV</label>
            <input class="field" type="file" name="file" accept=".xlsx,.xls,.csv" required>
        </div>
        <button class="btn-primary">Impor sekarang</button>
    </form>
</div>
@endsection
