@extends('layouts.app')

@section('title', 'Daftar Perkara')
@section('heading', 'Daftar Perkara & Barang Bukti')
@section('subheading', 'Isi header perkara, lalu tambah unit mandiri atau paket/wadah')

@section('content')
<form method="POST" action="{{ route('cases.store') }}" enctype="multipart/form-data" class="space-y-6"
      x-data="caseForm()">
    @csrf

    <div class="card grid gap-4 md:grid-cols-2">
        <h3 class="font-serif text-lg md:col-span-2">Header perkara</h3>
        <div>
            <label class="label">Nomor perkara</label>
            <input class="field" name="case_number" value="{{ old('case_number') }}" placeholder="REG-012/PID.SUS/2026" required>
        </div>
        <div>
            <label class="label">Nama JPU</label>
            <input class="field" name="prosecutor_name" value="{{ old('prosecutor_name') }}" required>
        </div>
        <div class="md:col-span-2">
            <label class="label">Nama terdakwa</label>
            <input class="field" name="defendant_name" value="{{ old('defendant_name') }}" required>
        </div>
        <div class="md:col-span-2">
            <label class="label">Catatan internal (opsional)</label>
            <textarea class="field" name="notes" rows="2">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-primary" @click="addSingle()">+ BB Mandiri (Satuan)</button>
        <button type="button" class="btn-outline" @click="addPack()">+ BB Paket (Wadah/Segel)</button>
    </div>

    <template x-for="(unit, index) in units" :key="unit.key">
        <div class="card space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gold-600" x-text="unit.type === 'SINGLE' ? 'Unit mandiri' : 'Paket / wadah'"></p>
                    <h3 class="font-serif text-lg" x-text="unit.type === 'SINGLE' ? 'BB satuan (stiker BB-…)' : 'Wadah tersegel (stiker PKT-…)'"></h3>
                </div>
                <button type="button" class="btn-outline text-red-700" @click="removeUnit(index)">Hapus</button>
            </div>

            <input type="hidden" :name="'units['+index+'][type]'" :value="unit.type">

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2" x-show="unit.type === 'SINGLE'">
                    <label class="label">Deskripsi barang</label>
                    <input class="field" :name="'units['+index+'][item_name]'" x-model="unit.item_name" placeholder="1 unit sepeda motor Honda Beat">
                </div>
                <div x-show="unit.type === 'SINGLE'">
                    <label class="label">Kategori</label>
                    <select class="field" :name="'units['+index+'][category]'" x-model="unit.category">
                        @foreach ($categories as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="unit.type === 'SINGLE'">
                    <label class="label">Jumlah / satuan</label>
                    <input class="field" :name="'units['+index+'][quantity]'" x-model="unit.quantity" placeholder="1 unit">
                </div>
                <div>
                    <label class="label">Lokasi gudang</label>
                    <input class="field" :name="'units['+index+'][storage_location]'" x-model="unit.storage_location" placeholder="Brankas PB3R Laci 02" required>
                </div>
                <div>
                    <label class="label">Foto unit</label>
                    <input class="field" type="file" accept="image/*" :name="'units['+index+'][photo]'">
                </div>
            </div>

            <div x-show="unit.type === 'PACK'" class="space-y-3 rounded-xl bg-navy-50 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold">Rincian isi paket (putusan bisa berbeda per item)</p>
                    <button type="button" class="btn-gold" @click="addChild(index)">+ Isi paket</button>
                </div>
                <template x-for="(child, cIndex) in unit.children" :key="child.key">
                    <div class="grid gap-3 rounded-lg bg-white p-3 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="label">Nama item</label>
                            <input class="field" :name="'units['+index+'][children]['+cIndex+'][item_name]'" x-model="child.item_name" placeholder="1 sachet sabu 0,5 gram">
                        </div>
                        <div class="md:col-span-3">
                            <label class="label">Kategori</label>
                            <select class="field" :name="'units['+index+'][children]['+cIndex+'][category]'" x-model="child.category">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}">{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="label">Jumlah</label>
                            <input class="field" :name="'units['+index+'][children]['+cIndex+'][quantity]'" x-model="child.quantity">
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <button type="button" class="btn-outline w-full" @click="removeChild(index, cIndex)">×</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <div class="flex gap-2">
        <button class="btn-primary">Simpan perkara & unit</button>
        <a class="btn-outline" href="{{ route('cases.index') }}">Batal</a>
    </div>
</form>

<script>
function caseForm() {
    const nextKey = () => Date.now() + Math.random();
    const child = () => ({ key: nextKey(), item_name: '', category: 'NARKOTIKA', quantity: '1' });
    return {
        units: [{
            key: nextKey(),
            type: 'SINGLE',
            item_name: '',
            category: 'NARKOTIKA',
            quantity: '1 unit',
            storage_location: '',
            children: [],
        }],
        addSingle() {
            this.units.push({
                key: nextKey(), type: 'SINGLE', item_name: '', category: 'NARKOTIKA',
                quantity: '1 unit', storage_location: '', children: [],
            });
        },
        addPack() {
            this.units.push({
                key: nextKey(), type: 'PACK', item_name: '', category: 'NARKOTIKA',
                quantity: '', storage_location: '', children: [child()],
            });
        },
        removeUnit(index) {
            if (this.units.length === 1) return;
            this.units.splice(index, 1);
        },
        addChild(index) {
            this.units[index].children.push(child());
        },
        removeChild(index, cIndex) {
            if (this.units[index].children.length === 1) return;
            this.units[index].children.splice(cIndex, 1);
        },
    };
}
</script>
@endsection
