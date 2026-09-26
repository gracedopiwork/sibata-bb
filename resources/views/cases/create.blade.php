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
            <label class="label">Jenis perkara</label>
            <select class="field" name="case_type_id" required>
                <option value="">Pilih jenis</option>
                @foreach ($caseTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('case_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="label">Nama terdakwa</label>
            <input class="field" name="defendant_name" value="{{ old('defendant_name') }}" required>
        </div>
        <div class="md:col-span-2">
            <label class="label">JPU</label>
            <div class="grid gap-2 rounded-xl border border-navy-100 p-3 md:grid-cols-2">
                @forelse ($prosecutors as $prosecutor)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="prosecutor_ids[]" value="{{ $prosecutor->id }}" @checked(in_array($prosecutor->id, old('prosecutor_ids', [])))>
                        <span>{{ $prosecutor->name }}@if($prosecutor->nip) <span class="text-navy-500">({{ $prosecutor->nip }})</span>@endif</span>
                    </label>
                @empty
                    <p class="text-sm text-navy-500 md:col-span-2">Belum ada JPU. Tambah dulu di menu Data Master → JPU.</p>
                @endforelse
            </div>
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
                            <option value="{{ $category->code }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="unit.type === 'SINGLE'">
                    <label class="label">Jumlah / satuan</label>
                    <input class="field" :name="'units['+index+'][quantity]'" x-model="unit.quantity" placeholder="1 unit">
                </div>
                <div>
                    <label class="label">Jenis aset</label>
                    <select class="field" :name="'units['+index+'][asset_type_id]'" x-model="unit.asset_type_id" required>
                        @foreach ($assetTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Tempat penyimpanan</label>
                    <select class="field" :name="'units['+index+'][storage_location_id]'" x-model="unit.storage_location_id" required>
                        @foreach ($storageLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Foto unit</label>
                    <input class="field" type="file" accept="image/*" :name="'units['+index+'][photo]'">
                </div>
            </div>

            <div x-show="unit.type === 'PACK'" class="space-y-3 rounded-xl bg-navy-50 p-4">
                <p class="text-sm font-semibold">Rincian isi paket</p>
                <p class="text-xs text-navy-600">Isi nama barang, pilih jenis, lalu tekan Tambah ke daftar. Segel tidak perlu dibuka.</p>
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="md:col-span-6">
                        <label class="label">Nama barang</label>
                        <input class="field" x-model="unit.draft_name" placeholder="1 sachet sabu 0,5 gram" @keydown.enter.prevent="addChild(index)">
                    </div>
                    <div class="md:col-span-4">
                        <label class="label">Jenis</label>
                        <select class="field" x-model="unit.draft_category">
                            @foreach ($categories as $category)
                                <option value="{{ $category->code }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end md:col-span-2">
                        <button type="button" class="btn-gold w-full" @click="addChild(index)">Tambah ke daftar</button>
                    </div>
                </div>
                <ol class="space-y-2">
                    <template x-for="(child, cIndex) in unit.children" :key="child.key">
                        <li class="flex items-start justify-between gap-3 rounded-lg bg-white px-3 py-2 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium" x-text="(cIndex + 1) + '. ' + child.item_name"></p>
                                <p class="text-xs text-navy-500" x-text="categoryNames[child.category] || child.category"></p>
                                <input type="hidden" :name="'units['+index+'][children]['+cIndex+'][item_name]'" :value="child.item_name">
                                <input type="hidden" :name="'units['+index+'][children]['+cIndex+'][category]'" :value="child.category">
                                <input type="hidden" :name="'units['+index+'][children]['+cIndex+'][quantity]'" :value="child.quantity">
                            </div>
                            <button type="button" class="btn-outline text-red-700" @click="removeChild(index, cIndex)">Hapus</button>
                        </li>
                    </template>
                </ol>
                <p class="text-xs text-navy-500" x-show="unit.children.length === 0">Belum ada isi. Tambah satu per satu di atas.</p>
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
    const defaultCategory = @json($categories->first()?->code ?? 'NARKOTIKA');
    const defaultAssetType = @json($assetTypes->first()?->id);
    const defaultStorageLocation = @json($storageLocations->first()?->id);
    const categoryNames = @json($categories->mapWithKeys(fn ($category) => [$category->code => $category->name]));
    const emptyPack = () => ({
        key: nextKey(), type: 'PACK', item_name: '', category: defaultCategory,
        asset_type_id: defaultAssetType, quantity: '',
        storage_location_id: defaultStorageLocation, children: [],
        draft_name: '', draft_category: defaultCategory,
    });
    return {
        categoryNames,
        units: [{
            key: nextKey(),
            type: 'SINGLE',
            item_name: '',
            category: defaultCategory,
            asset_type_id: defaultAssetType,
            quantity: '1 unit',
            storage_location_id: defaultStorageLocation,
            children: [],
            draft_name: '',
            draft_category: defaultCategory,
        }],
        addSingle() {
            this.units.push({
                key: nextKey(), type: 'SINGLE', item_name: '', category: defaultCategory,
                asset_type_id: defaultAssetType, quantity: '1 unit',
                storage_location_id: defaultStorageLocation, children: [],
                draft_name: '', draft_category: defaultCategory,
            });
        },
        addPack() {
            this.units.push(emptyPack());
        },
        removeUnit(index) {
            if (this.units.length === 1) return;
            this.units.splice(index, 1);
        },
        addChild(index) {
            const unit = this.units[index];
            const name = (unit.draft_name || '').trim();
            if (!name) {
                return;
            }
            unit.children.push({
                key: nextKey(),
                item_name: name,
                category: unit.draft_category || defaultCategory,
                quantity: '1',
            });
            unit.draft_name = '';
        },
        removeChild(index, cIndex) {
            this.units[index].children.splice(cIndex, 1);
        },
    };
}
</script>
@endsection
