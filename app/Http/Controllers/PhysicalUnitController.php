<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Enums\VerdictStatus;
use App\Models\EvidenceCategory;
use App\Models\PhysicalUnit;
use App\Models\UnitItem;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PhysicalUnitController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouse) {}

    public function index(Request $request): View
    {
        $units = PhysicalUnit::query()
            ->with(['legalCase', 'items', 'assetType'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('current_status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('unit_type', $request->string('type')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('units.index', [
            'units' => $units,
            'statuses' => UnitStatus::cases(),
            'types' => UnitType::cases(),
        ]);
    }

    public function show(PhysicalUnit $unit): View
    {
        $unit->load(['legalCase', 'items', 'mutations', 'assetType']);

        return view('units.show', [
            'unit' => $unit,
            'verdicts' => array_values(array_filter(
                VerdictStatus::cases(),
                fn (VerdictStatus $status) => $status->isFinal()
            )),
            'categories' => EvidenceCategory::active()->get(),
        ]);
    }

    public function loan(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:255'],
            'court_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->warehouse->loan(
                $unit,
                $data['borrower_name'],
                $data['court_date'],
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['borrower_name' => $exception->getMessage()]);
        }

        return back()->with('status', 'Unit ditandai dipinjam sidang.');
    }

    public function returnToWarehouse(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'storage_location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->warehouse->returnToWarehouse(
                $unit,
                $data['storage_location'],
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['storage_location' => $exception->getMessage()]);
        }

        return back()->with('status', 'Unit dikembalikan ke gudang.');
    }

    public function addChild(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'exists:evidence_categories,code'],
            'quantity' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->warehouse->addPackChild(
                $unit,
                $data['item_name'],
                $data['category'],
                $data['quantity'],
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['item_name' => $exception->getMessage()]);
        }

        return back()->with('status', 'Rincian isi paket ditambahkan.');
    }

    public function execute(Request $request, PhysicalUnit $unit, UnitItem $item): RedirectResponse
    {
        abort_unless($item->physical_unit_id === $unit->id, 404);

        $data = $request->validate([
            'verdict_status' => ['required', 'string'],
            'execution_ba_number' => ['nullable', 'string', 'max:100'],
            'execution_recipient' => ['nullable', 'string', 'max:255'],
            'execution_recipient_nik' => ['nullable', 'string', 'max:20'],
            'execution_proof_photo' => ['nullable', 'image', 'max:8192'],
            'notes' => ['nullable', 'string'],
        ]);

        $verdict = VerdictStatus::from($data['verdict_status']);
        abort_unless($verdict->isFinal(), 422);

        $photoPath = null;
        if ($request->hasFile('execution_proof_photo')) {
            $photoPath = $request->file('execution_proof_photo')->store('executions', 'public');
        }

        $this->warehouse->executeItem(
            $item,
            $verdict,
            $request->user()?->name ?? 'Web PB3R',
            $data['execution_ba_number'] ?? null,
            $data['execution_recipient'] ?? null,
            $data['execution_recipient_nik'] ?? null,
            $photoPath,
            $data['notes'] ?? null,
        );

        return back()->with('status', 'Eksekusi putusan tercatat.');
    }
}
