<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Enums\VerdictStatus;
use App\Models\EvidenceCategory;
use App\Models\PhysicalUnit;
use App\Models\Prosecutor;
use App\Models\StorageLocation;
use App\Models\UnitItem;
use App\Models\UnitPhoto;
use App\Services\PackContentsParser;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhysicalUnitController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouse,
        private readonly PackContentsParser $packContents,
    ) {}

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
        $unit->load(['legalCase', 'items', 'mutations', 'assetType', 'storageLocation']);

        return view('units.show', [
            'unit' => $unit,
            'verdicts' => array_values(array_filter(
                VerdictStatus::cases(),
                fn (VerdictStatus $status) => $status->isFinal()
            )),
            'categories' => EvidenceCategory::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'prosecutors' => Prosecutor::active()->get(),
        ]);
    }

    public function loan(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['integer', 'exists:prosecutors,id'],
            'court_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:12288'],
        ]);

        $borrower = Prosecutor::query()
            ->whereIn('id', $data['prosecutor_ids'])
            ->orderBy('name')
            ->pluck('name')
            ->implode('; ');

        try {
            $this->warehouse->loan(
                $unit,
                $borrower,
                $data['court_date'],
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
                $request->file('photo')->store('loans', 'public'),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['prosecutor_ids' => $exception->getMessage()]);
        }

        return redirect()->route('loans.index')->with('status', 'Peminjaman BB tercatat.');
    }

    public function returnToWarehouse(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'storage_location_id' => ['required', 'exists:storage_locations,id'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:12288'],
        ]);

        $location = StorageLocation::query()->findOrFail((int) $data['storage_location_id']);

        try {
            $this->warehouse->returnToWarehouse(
                $unit,
                $location->name,
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
                $location->id,
                $request->file('photo')->store('loans', 'public'),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['storage_location_id' => $exception->getMessage()]);
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

    public function addChildrenBulk(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'contents_bulk' => ['required', 'string'],
        ]);

        $items = $this->packContents->parse($data['contents_bulk']);

        if ($items === []) {
            return back()->withErrors(['contents_bulk' => 'Tidak ada baris yang bisa dibaca.']);
        }

        try {
            $count = $this->warehouse->addPackChildren($unit, $items);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['contents_bulk' => $exception->getMessage()]);
        }

        return back()->with('status', $count.' isi paket ditambahkan dari daftar.');
    }

    public function photo(PhysicalUnit $unit): Response|StreamedResponse
    {
        $record = UnitPhoto::query()->where('physical_unit_id', $unit->id)->first();
        $binary = $record?->data;
        if (is_resource($binary)) {
            $binary = stream_get_contents($binary);
        }

        if (is_string($binary) && $binary !== '') {
            return response($binary, 200, [
                'Content-Type' => $record->mime ?: 'image/jpeg',
                'Cache-Control' => 'private, max-age=86400',
            ]);
        }

        if (is_string($unit->photo_path) && $unit->photo_path !== '' && Storage::disk('public')->exists($unit->photo_path)) {
            return Storage::disk('public')->response($unit->photo_path);
        }

        abort(404);
    }

    public function updatePhoto(Request $request, PhysicalUnit $unit): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:12288'],
        ]);

        $this->warehouse->attachPhoto($unit, $data['photo']);

        return back()->with('status', 'Foto unit tersimpan.');
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
