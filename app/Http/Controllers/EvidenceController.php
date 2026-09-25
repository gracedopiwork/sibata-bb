<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceStatus;
use App\Http\Requests\ExecuteEvidenceRequest;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\UpdateEvidenceRequest;
use App\Models\EvidenceItem;
use App\Services\EvidenceLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidenceController extends Controller
{
    public function __construct(private readonly EvidenceLogService $logs) {}

    public function index(Request $request): View
    {
        $items = EvidenceItem::query()
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('evidence.index', [
            'items' => $items,
            'statuses' => EvidenceStatus::cases(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        return view('evidence.create', [
            'statuses' => EvidenceStatus::cases(),
        ]);
    }

    public function store(StoreEvidenceRequest $request): RedirectResponse
    {
        $item = EvidenceItem::query()->create([
            ...$request->validated(),
            'qr_token' => EvidenceItem::nextQrToken(),
            'status' => $request->input('status', EvidenceStatus::Tersedia->value),
        ]);

        $this->logs->register($item, $request->user());

        return redirect()
            ->route('evidence.show', $item)
            ->with('status', 'Barang bukti berhasil didaftarkan. Token QR: '.$item->qr_token);
    }

    public function show(EvidenceItem $evidence): View
    {
        $evidence->load(['logs.user']);

        return view('evidence.show', [
            'item' => $evidence,
            'executionStatuses' => EvidenceStatus::executionCases(),
        ]);
    }

    public function edit(EvidenceItem $evidence): View
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        return view('evidence.edit', [
            'item' => $evidence,
            'statuses' => EvidenceStatus::cases(),
        ]);
    }

    public function update(UpdateEvidenceRequest $request, EvidenceItem $evidence): RedirectResponse
    {
        $oldLocation = $evidence->lokasi_rak;
        $evidence->update($request->safe()->except('lokasi_rak'));

        if ($oldLocation !== $request->string('lokasi_rak')->toString()) {
            $this->logs->relocate($evidence, $request->user(), $oldLocation, $request->string('lokasi_rak')->toString());
        }

        return redirect()
            ->route('evidence.show', $evidence)
            ->with('status', 'Data barang bukti diperbarui.');
    }

    public function destroy(EvidenceItem $evidence): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        $evidence->delete();

        return redirect()
            ->route('evidence.index')
            ->with('status', 'Barang bukti dihapus.');
    }

    public function execute(ExecuteEvidenceRequest $request, EvidenceItem $evidence): RedirectResponse
    {
        $status = EvidenceStatus::from($request->string('status')->toString());
        $this->logs->execute($evidence, $request->user(), $status, $request->input('notes'));

        return redirect()
            ->route('evidence.show', $evidence)
            ->with('status', 'Status eksekusi diperbarui menjadi '.$status->label().'.');
    }
}
