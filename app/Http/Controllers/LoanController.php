<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Models\EvidenceLoan;
use App\Models\LoanPhoto;
use App\Models\PhysicalUnit;
use App\Models\Prosecutor;
use App\Models\StorageLocation;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouse) {}

    public function index(Request $request): View
    {
        $loans = EvidenceLoan::query()
            ->with(['physicalUnit.legalCase', 'outboundPhoto', 'inboundPhoto'])
            ->search($request->string('q')->toString())
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->whereNull('returned_at'))
            ->when($request->string('status')->toString() === 'returned', fn ($query) => $query->whereNotNull('returned_at'))
            ->latest('loaned_at')
            ->paginate(20)
            ->withQueryString();

        return view('loans.index', compact('loans'));
    }

    public function create(): View
    {
        return view('loans.create', [
            'units' => PhysicalUnit::query()
                ->with('legalCase')
                ->where('current_status', UnitStatus::TersimpanGudang)
                ->latest()
                ->get(),
            'prosecutors' => Prosecutor::active()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'physical_unit_id' => ['required', 'exists:physical_units,id'],
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['integer', 'exists:prosecutors,id'],
            'court_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:12288'],
        ]);

        $unit = PhysicalUnit::query()->findOrFail((int) $data['physical_unit_id']);
        $borrower = Prosecutor::query()
            ->whereIn('id', $data['prosecutor_ids'])
            ->orderBy('name')
            ->pluck('name')
            ->implode('; ');
        $photoPath = $request->file('photo')->store('loans', 'public');

        try {
            $this->warehouse->loan(
                $unit,
                $borrower,
                $data['court_date'],
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
                $photoPath,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['physical_unit_id' => $exception->getMessage()])->withInput();
        }

        $loan = EvidenceLoan::query()
            ->where('physical_unit_id', $unit->id)
            ->whereNull('returned_at')
            ->latest('id')
            ->firstOrFail();

        return redirect()->route('loans.show', $loan)->with('status', 'Peminjaman BB tercatat.');
    }

    public function show(EvidenceLoan $loan): View
    {
        $loan->load(['physicalUnit.legalCase', 'physicalUnit.items', 'outboundPhoto', 'inboundPhoto', 'returnStorageLocation']);

        return view('loans.show', [
            'loan' => $loan,
            'storageLocations' => StorageLocation::active()->get(),
        ]);
    }

    public function returnLoan(Request $request, EvidenceLoan $loan): RedirectResponse
    {
        abort_unless($loan->isActive(), 422, 'Peminjaman ini sudah dikembalikan.');

        $data = $request->validate([
            'storage_location_id' => ['required', 'exists:storage_locations,id'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:12288'],
        ]);

        $location = StorageLocation::query()->findOrFail((int) $data['storage_location_id']);
        $photoPath = $request->file('photo')->store('loans', 'public');

        try {
            $this->warehouse->returnToWarehouse(
                $loan->physicalUnit,
                $location->name,
                $request->user()?->name ?? 'Web PB3R',
                $data['notes'] ?? null,
                $location->id,
                $photoPath,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['storage_location_id' => $exception->getMessage()]);
        }

        return redirect()->route('loans.show', $loan->fresh())->with('status', 'BB dikembalikan ke gudang.');
    }

    public function photo(EvidenceLoan $loan, string $kind): Response|StreamedResponse
    {
        abort_unless(in_array($kind, ['loan', 'return'], true), 404);

        $record = LoanPhoto::query()
            ->where('bb_loan_id', $loan->id)
            ->where('kind', $kind)
            ->first();

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

        $path = $kind === 'loan' ? $loan->loan_photo_path : $loan->return_photo_path;
        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path);
        }

        abort(404);
    }
}
