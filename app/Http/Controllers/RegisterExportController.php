<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceActionType;
use App\Exports\EvidenceExport;
use App\Exports\LoanRegisterExport;
use App\Models\EvidenceItem;
use App\Models\EvidenceLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegisterExportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function evidencePdf(Request $request): Response
    {
        $items = $this->evidenceQuery($request)->get();

        $pdf = Pdf::loadView('pdf.register-b4', [
            'items' => $items,
            'filters' => $this->filters($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('register-form-b4.pdf');
    }

    public function evidenceExcel(Request $request): BinaryFileResponse
    {
        return Excel::download(new EvidenceExport($this->evidenceQuery($request)), 'register-form-b4.xlsx');
    }

    public function loansPdf(Request $request): Response
    {
        $logs = $this->loanQuery($request)->get();

        $pdf = Pdf::loadView('pdf.register-loans', [
            'logs' => $logs,
            'filters' => $this->filters($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('register-peminjaman-bb.pdf');
    }

    public function loansExcel(Request $request): BinaryFileResponse
    {
        return Excel::download(new LoanRegisterExport($this->loanQuery($request)), 'register-peminjaman-bb.xlsx');
    }

    public function photo(EvidenceLog $log): StreamedResponse
    {
        abort_unless($log->hasPhoto(), 404);

        return Storage::disk('local')->response($log->photo_proof_path);
    }

    private function evidenceQuery(Request $request)
    {
        return EvidenceItem::query()
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->orderBy('qr_token');
    }

    private function loanQuery(Request $request)
    {
        return EvidenceLog::query()
            ->with(['evidence', 'user'])
            ->where('action_type', EvidenceActionType::Pinjam)
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest();
    }

    /**
     * @return array<string, string|null>
     */
    private function filters(Request $request): array
    {
        return [
            'q' => $request->string('q')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];
    }
}
