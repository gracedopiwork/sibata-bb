<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetTypeController extends Controller
{
    public function index(): View
    {
        return view('masters.asset-types.index', [
            'types' => AssetType::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        AssetType::query()->create($this->payload($request));

        return redirect()->route('asset-types.index')->with('status', 'Jenis aset ditambahkan.');
    }

    public function edit(AssetType $asset_type): View
    {
        return view('masters.asset-types.edit', [
            'type' => $asset_type,
        ]);
    }

    public function update(Request $request, AssetType $asset_type): RedirectResponse
    {
        $asset_type->update($this->payload($request, $asset_type->id));

        return redirect()->route('asset-types.index')->with('status', 'Jenis aset diperbarui.');
    }

    public function destroy(AssetType $asset_type): RedirectResponse
    {
        abort_if($asset_type->physicalUnits()->exists(), 422, 'Jenis aset ini sudah dipakai pada barang bukti.');

        $asset_type->delete();

        return redirect()->route('asset-types.index')->with('status', 'Jenis aset dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('asset_types', 'code')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code'] ?: Str::slug($data['name'], '_'));
        $data['is_active'] = $request->boolean('is_active', $ignoreId === null);

        return $data;
    }
}
