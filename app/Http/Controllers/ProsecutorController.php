<?php

namespace App\Http\Controllers;

use App\Models\Prosecutor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProsecutorController extends Controller
{
    public function index(): View
    {
        return view('masters.prosecutors.index', [
            'prosecutors' => Prosecutor::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['nip'] = $data['nip'] ?: null;

        Prosecutor::query()->create($data);

        return redirect()->route('prosecutors.index')->with('status', 'Data JPU ditambahkan.');
    }

    public function edit(Prosecutor $prosecutor): View
    {
        return view('masters.prosecutors.edit', [
            'prosecutor' => $prosecutor,
        ]);
    }

    public function update(Request $request, Prosecutor $prosecutor): RedirectResponse
    {
        $data = $this->validated($request, $prosecutor->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['nip'] = $data['nip'] ?: null;

        $prosecutor->update($data);

        return redirect()->route('prosecutors.index')->with('status', 'Data JPU diperbarui.');
    }

    public function destroy(Prosecutor $prosecutor): RedirectResponse
    {
        abort_if($prosecutor->cases()->exists(), 422, 'JPU ini sudah dipakai di perkara.');

        $prosecutor->delete();

        return redirect()->route('prosecutors.index')->with('status', 'Data JPU dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('prosecutors', 'nip')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
