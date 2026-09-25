<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\ItemCategory;
use App\Enums\UnitType;
use App\Http\Requests\StoreLegalCaseRequest;
use App\Http\Requests\UpdateLegalCaseRequest;
use App\Models\LegalCase;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalCaseController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouse) {}

    public function index(Request $request): View
    {
        $cases = LegalCase::query()
            ->withCount('physicalUnits')
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('case_status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cases.index', [
            'cases' => $cases,
            'statuses' => CaseStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('cases.create', [
            'categories' => ItemCategory::cases(),
        ]);
    }

    public function store(StoreLegalCaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $handler = $request->user()?->name ?? 'Web PB3R';

        $case = LegalCase::query()->create([
            'case_number' => $data['case_number'],
            'defendant_name' => $data['defendant_name'],
            'prosecutor_name' => $data['prosecutor_name'],
            'notes' => $data['notes'] ?? null,
            'case_status' => CaseStatus::Tahap2,
        ]);

        $createdIds = [];

        foreach ($data['units'] as $index => $unitInput) {
            $photoPath = null;
            $uploaded = $request->file("units.{$index}.photo");

            if ($uploaded !== null) {
                $photoPath = $uploaded->store('units', 'public');
            }

            $type = UnitType::from($unitInput['type']);

            if ($type === UnitType::Single) {
                $unit = $this->warehouse->createSingleUnit(
                    $case,
                    $unitInput['item_name'],
                    ItemCategory::from($unitInput['category']),
                    $unitInput['quantity'] ?? '1',
                    $unitInput['storage_location'],
                    $photoPath,
                    $handler,
                );
            } else {
                $children = collect($unitInput['children'] ?? [])
                    ->filter(fn ($child) => filled($child['item_name'] ?? null))
                    ->map(fn ($child) => [
                        'item_name' => $child['item_name'],
                        'category' => ItemCategory::from($child['category']),
                        'quantity' => $child['quantity'] ?? '1',
                    ])
                    ->all();

                $unit = $this->warehouse->createPackUnit(
                    $case,
                    $unitInput['storage_location'],
                    $photoPath,
                    $handler,
                    $children,
                );
            }

            $createdIds[] = $unit->id;
        }

        return redirect()
            ->route('cases.show', $case)
            ->with('status', 'Perkara dan unit fisik berhasil dicatat.')
            ->with('print_ids', $createdIds);
    }

    public function show(LegalCase $case): View
    {
        $case->load(['physicalUnits.items', 'physicalUnits.mutations']);

        return view('cases.show', [
            'case' => $case,
            'printIds' => session('print_ids', []),
        ]);
    }

    public function edit(LegalCase $case): View
    {
        return view('cases.edit', [
            'case' => $case,
            'statuses' => CaseStatus::cases(),
        ]);
    }

    public function update(UpdateLegalCaseRequest $request, LegalCase $case): RedirectResponse
    {
        $case->update($request->validated());

        return redirect()->route('cases.show', $case)->with('status', 'Data perkara diperbarui.');
    }
}
