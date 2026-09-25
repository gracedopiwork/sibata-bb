<?php

namespace App\Http\Controllers;

use App\Models\PhysicalUnit;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintLabelController extends Controller
{
    public function index(Request $request): View
    {
        $ids = $this->parseIds($request);

        $units = PhysicalUnit::query()
            ->with(['legalCase', 'items'])
            ->when($ids !== [], fn ($query) => $query->whereIn('id', $ids))
            ->when($ids === [] && ! $request->boolean('all'), fn ($query) => $query->where('is_printed', false))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('print.index', [
            'units' => $units,
            'selectedIds' => $ids,
        ]);
    }

    public function sheet(Request $request, QrCodeService $qrCode): View
    {
        $ids = $this->parseIds($request);
        abort_if($ids === [], 422, 'Pilih minimal satu unit untuk dicetak.');

        $units = PhysicalUnit::query()
            ->with(['legalCase', 'items'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function (PhysicalUnit $unit) use ($qrCode) {
                $unit->qr_data_uri = $qrCode->pngDataUri($unit->publicViewUrl(), 8);

                return $unit;
            });

        return view('print.sheet', [
            'units' => $units,
            'ids' => $units->pluck('id')->all(),
        ]);
    }

    public function markPrinted(Request $request): RedirectResponse
    {
        $ids = $this->parseIds($request);
        abort_if($ids === [], 422, 'Tidak ada unit yang ditandai.');

        PhysicalUnit::query()->whereIn('id', $ids)->update(['is_printed' => true]);

        return redirect()
            ->route('print-labels.index')
            ->with('status', count($ids).' label ditandai sudah dicetak.');
    }

    /**
     * @return list<int>
     */
    private function parseIds(Request $request): array
    {
        $raw = $request->input('ids', $request->query('ids'));

        if (is_string($raw)) {
            $raw = preg_split('/[,\s]+/', $raw) ?: [];
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $raw), fn (int $id) => $id > 0));
    }
}
