<?php

namespace App\Http\Controllers;

use App\Imports\EvidenceImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EvidenceImportController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        return view('evidence.import');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new EvidenceImport($request->user());

        try {
            Excel::import($import, $request->file('file'));
        } catch (Throwable $exception) {
            return back()->withErrors([
                'file' => 'Impor gagal: '.$exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('evidence.index')
            ->with('status', "Impor selesai. Berhasil: {$import->importedCount()} baris. Gagal: {$import->failedCount()} baris.");
    }

    public function template(): StreamedResponse
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-impor-bb.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'no_reg_bb',
                'no_reg_perkara',
                'nama_terdakwa',
                'nama_barang',
                'jumlah_satuan',
                'lokasi_rak',
                'status',
            ]);
            fputcsv($handle, [
                'BB-001/WJO/2026',
                'PDS-01/WJO/2026',
                'Andi Rahman',
                '1 unit sepeda motor Honda Beat',
                '1 Unit',
                'Lemari Besi A-01',
                'TERSEDIA',
            ]);
            fclose($handle);
        }, 200, $headers);
    }
}
