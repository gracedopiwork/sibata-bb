<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\UnitType;
use App\Http\Requests\StoreLegalCaseRequest;
use App\Http\Requests\UpdateLegalCaseRequest;
use App\Models\AssetType;
use App\Models\StorageLocation;
use App\Models\CaseType;
use App\Models\EvidenceCategory;
use App\Models\LegalCase;
use App\Models\Prosecutor;
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
            ->with(['caseType'])
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
            'categories' => EvidenceCategory::active()->get(),
            'assetTypes' => AssetType::active()->get(),
            'prosecutors' => Prosecutor::active()->get(),
            'caseTypes' => CaseType::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
        ]);
    }

    public function store(StoreLegalCaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $handler = $request->user()?->name ?? 'Web PB3R';

        $prosecutorIds = $data['prosecutor_ids'];
        $prosecutorNames = Prosecutor::query()
            ->whereIn('id', $prosecutorIds)
            ->orderBy('name')
            ->pluck('name')
            ->implode('; ');

        $case = LegalCase::query()->create([
            'case_number' => $data['case_number'],
            'defendant_name' => $data['defendant_name'],
            'prosecutor_name' => $prosecutorNames,
            'case_type_id' => $data['case_type_id'],
            'notes' => $data['notes'] ?? null,
            'case_status' => CaseStatus::Tahap2,
        ]);
        $case->prosecutors()->sync($prosecutorIds);

        $createdIds = [];

        foreach ($data['units'] as $index => $unitInput) {
            $photoPath = null;
            $uploaded = $request->file("units.{$index}.photo");

            if ($uploaded !== null) {
                $photoPath = $uploaded->store('units', 'public');
            }

            $type = UnitType::from($unitInput['type']);
            $location = StorageLocation::query()->find((int) $unitInput['storage_location_id']);
            $locationName = $location?->name ?? '';
            $locationId = $location?->id;

            if ($type === UnitType::Single) {
                $unit = $this->warehouse->createSingleUnit(
                    $case,
                    $unitInput['item_name'],
                    (string) $unitInput['category'],
                    $unitInput['quantity'] ?? '1',
                    $locationName,
                    $photoPath,
                    $handler,
                    isset($unitInput['asset_type_id']) ? (int) $unitInput['asset_type_id'] : null,
                    $locationId,
                );
            } else {
                $children = collect($unitInput['children'] ?? [])
                    ->filter(fn ($child) => filled($child['item_name'] ?? null))
                    ->map(fn ($child) => [
                        'item_name' => $child['item_name'],
                        'category' => (string) $child['category'],
                        'quantity' => $child['quantity'] ?? '1',
                    ])
                    ->all();

                $unit = $this->warehouse->createPackUnit(
                    $case,
                    $locationName,
                    $photoPath,
                    $handler,
                    $children,
                    isset($unitInput['asset_type_id']) ? (int) $unitInput['asset_type_id'] : null,
                    $locationId,
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
        $case->load(['caseType', 'prosecutors', 'physicalUnits.items', 'physicalUnits.mutations']);

        return view('cases.show', [
            'case' => $case,
            'printIds' => session('print_ids', []),
        ]);
    }

    public function edit(LegalCase $case): View
    {
        $case->load('prosecutors');

        return view('cases.edit', [
            'case' => $case,
            'statuses' => CaseStatus::cases(),
            'prosecutors' => Prosecutor::active()->get(),
            'caseTypes' => CaseType::active()->get(),
        ]);
    }

    public function update(UpdateLegalCaseRequest $request, LegalCase $case): RedirectResponse
    {
        $data = $request->validated();
        $prosecutorIds = $data['prosecutor_ids'];
        $data['prosecutor_name'] = Prosecutor::query()
            ->whereIn('id', $prosecutorIds)
            ->orderBy('name')
            ->pluck('name')
            ->implode('; ');
        unset($data['prosecutor_ids']);

        $case->update($data);
        $case->prosecutors()->sync($prosecutorIds);

        return redirect()->route('cases.show', $case)->with('status', 'Data perkara diperbarui.');
    }
}
