<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseTypeController extends Controller
{
    public function index(): View
    {
        return view('masters.case-types.index', [
            'types' => CaseType::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CaseType::query()->create($this->payload($request));

        return redirect()->route('case-types.index')->with('status', 'Jenis perkara ditambahkan.');
    }

    public function edit(CaseType $case_type): View
    {
        return view('masters.case-types.edit', [
            'type' => $case_type,
        ]);
    }

    public function update(Request $request, CaseType $case_type): RedirectResponse
    {
        $case_type->update($this->payload($request, $case_type->id));

        return redirect()->route('case-types.index')->with('status', 'Jenis perkara diperbarui.');
    }

    public function destroy(CaseType $case_type): RedirectResponse
    {
        abort_if($case_type->cases()->exists(), 422, 'Jenis perkara ini sudah dipakai.');

        $case_type->delete();

        return redirect()->route('case-types.index')->with('status', 'Jenis perkara dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('case_types', 'code')->ignore($ignoreId)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code'] ?: Str::slug($data['name'], '_'));
        $data['is_active'] = $request->boolean('is_active', $ignoreId === null);

        return $data;
    }
}
