<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorageLocationController extends Controller
{
    public function index(): View
    {
        return view('masters.storage-locations.index', [
            'locations' => StorageLocation::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StorageLocation::query()->create($this->payload($request));

        return redirect()->route('storage-locations.index')->with('status', 'Tempat penyimpanan ditambahkan.');
    }

    public function edit(StorageLocation $storage_location): View
    {
        return view('masters.storage-locations.edit', [
            'location' => $storage_location,
        ]);
    }

    public function update(Request $request, StorageLocation $storage_location): RedirectResponse
    {
        $storage_location->update($this->payload($request, $storage_location->id));

        return redirect()->route('storage-locations.index')->with('status', 'Tempat penyimpanan diperbarui.');
    }

    public function destroy(StorageLocation $storage_location): RedirectResponse
    {
        abort_if($storage_location->physicalUnits()->exists(), 422, 'Tempat penyimpanan ini sudah dipakai.');

        $storage_location->delete();

        return redirect()->route('storage-locations.index')->with('status', 'Tempat penyimpanan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('storage_locations', 'code')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code'] ?: Str::slug($data['name'], '_'));
        $data['is_active'] = $request->boolean('is_active', $ignoreId === null);

        return $data;
    }
}
