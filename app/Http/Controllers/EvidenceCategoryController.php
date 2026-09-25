<?php

namespace App\Http\Controllers;

use App\Models\EvidenceCategory;
use App\Models\UnitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvidenceCategoryController extends Controller
{
    public function index(): View
    {
        return view('masters.categories.index', [
            'categories' => EvidenceCategory::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EvidenceCategory::query()->create($this->payload($request));

        return redirect()->route('evidence-categories.index')->with('status', 'Jenis BB ditambahkan.');
    }

    public function edit(EvidenceCategory $evidence_category): View
    {
        return view('masters.categories.edit', [
            'category' => $evidence_category,
        ]);
    }

    public function update(Request $request, EvidenceCategory $evidence_category): RedirectResponse
    {
        $evidence_category->update($this->payload($request, $evidence_category->id));

        return redirect()->route('evidence-categories.index')->with('status', 'Jenis BB diperbarui.');
    }

    public function destroy(EvidenceCategory $evidence_category): RedirectResponse
    {
        abort_if(
            UnitItem::query()->where('category', $evidence_category->code)->exists(),
            422,
            'Jenis BB ini sudah dipakai pada barang bukti.'
        );

        $evidence_category->delete();

        return redirect()->route('evidence-categories.index')->with('status', 'Jenis BB dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('evidence_categories', 'code')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code'] ?: Str::slug($data['name'], '_'));
        $data['is_active'] = $request->boolean('is_active', $ignoreId === null);

        return $data;
    }
}
