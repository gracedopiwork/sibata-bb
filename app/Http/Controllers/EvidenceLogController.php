<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceActionType;
use App\Models\EvidenceLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidenceLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = EvidenceLog::query()
            ->with(['evidence', 'user'])
            ->when($request->filled('action'), fn ($query) => $query->where('action_type', $request->string('action')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($builder) use ($term) {
                    $builder->where('borrower_name', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('evidence', function ($evidence) use ($term) {
                            $evidence->where('qr_token', 'like', $term)
                                ->orWhere('no_reg_bb', 'like', $term)
                                ->orWhere('nama_terdakwa', 'like', $term);
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'actions' => EvidenceActionType::cases(),
        ]);
    }
}
