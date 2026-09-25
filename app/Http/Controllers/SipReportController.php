<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Enums\VerdictStatus;
use App\Exports\SipRegisterExport;
use App\Models\UnitItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SipReportController extends Controller
{
    public function index(Request $request): View
    {
        $items = $this->query($request)
            ->paginate(25)
            ->withQueryString();

        return view('reports.sip', [
            'items' => $items,
            'unitStatuses' => UnitStatus::cases(),
            'verdicts' => VerdictStatus::cases(),
        ]);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new SipRegisterExport($this->query($request)),
            'register-pb3r-sitaba-bb.xlsx'
        );
    }

    private function query(Request $request)
    {
        return UnitItem::query()
            ->with(['physicalUnit.legalCase'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q')->toString().'%';
                $query->where(function ($builder) use ($like) {
                    $builder->where('item_name', 'like', $like)
                        ->orWhereHas('physicalUnit', function ($unit) use ($like) {
                            $unit->where('unit_code', 'like', $like)
                                ->orWhere('storage_location', 'like', $like)
                                ->orWhereHas('legalCase', function ($case) use ($like) {
                                    $case->where('case_number', 'like', $like)
                                        ->orWhere('defendant_name', 'like', $like);
                                });
                        });
                });
            })
            ->when($request->filled('unit_status'), function ($query) use ($request) {
                $query->whereHas('physicalUnit', fn ($unit) => $unit->where('current_status', $request->string('unit_status')));
            })
            ->when($request->filled('verdict_status'), fn ($query) => $query->where('verdict_status', $request->string('verdict_status')))
            ->orderByDesc('sip_evidence_items.id');
    }
}
