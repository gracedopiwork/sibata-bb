@php($item = $item ?? null)
<div>
    <label class="label">No. Register BB (Form B-4)</label>
    <input class="field" name="no_reg_bb" value="{{ old('no_reg_bb', $item->no_reg_bb ?? '') }}" required>
</div>
<div>
    <label class="label">No. Register Perkara</label>
    <input class="field" name="no_reg_perkara" value="{{ old('no_reg_perkara', $item->no_reg_perkara ?? '') }}" required>
</div>
<div class="md:col-span-2">
    <label class="label">Nama terdakwa</label>
    <input class="field" name="nama_terdakwa" value="{{ old('nama_terdakwa', $item->nama_terdakwa ?? '') }}" required>
</div>
<div class="md:col-span-2">
    <label class="label">Nama barang</label>
    <textarea class="field" name="nama_barang" rows="3" required>{{ old('nama_barang', $item->nama_barang ?? '') }}</textarea>
</div>
<div>
    <label class="label">Jumlah / satuan</label>
    <input class="field" name="jumlah_satuan" value="{{ old('jumlah_satuan', $item->jumlah_satuan ?? '') }}" placeholder="1 Unit, 3 Paket" required>
</div>
<div>
    <label class="label">Lokasi rak</label>
    <input class="field" name="lokasi_rak" value="{{ old('lokasi_rak', $item->lokasi_rak ?? '') }}" placeholder="Lemari Besi A-02" required>
</div>
@if(isset($item))
<div class="md:col-span-2">
    <label class="label">Status</label>
    <select class="field" name="status" required>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $item->status->value) === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
</div>
@endif
