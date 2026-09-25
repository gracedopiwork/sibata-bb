<?php

namespace App\Http\Controllers;

use App\Models\EvidenceItem;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EvidenceLabelController extends Controller
{
    public function __construct(private readonly QrCodeService $qrCode) {}

    public function show(EvidenceItem $evidence): Response
    {
        $pdf = Pdf::loadView('pdf.label', [
            'row' => $this->present($evidence),
        ]);

        $pdf->setPaper([0, 0, 311.81, 198.43]);
        $pdf->setOption('dpi', 150);
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->stream('stiker-'.$evidence->qr_token.'.pdf');
    }

    public function bulk(Request $request): Response
    {
        abort_unless(auth()->user()?->canManageEvidence(), 403);

        $ids = collect(explode(',', (string) $request->string('ids')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = EvidenceItem::query()->orderBy('qr_token');

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }

        $items = $query->get()->map(fn (EvidenceItem $item) => $this->present($item));

        $pdf = Pdf::loadView('pdf.labels-bulk', [
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('dpi', 150);
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('isHtml5ParserEnabled', true);

        return $pdf->stream('stiker-bb-massal.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(EvidenceItem $item): array
    {
        return [
            'item' => $item,
            'qr' => $this->qrCode->pngDataUri($item->qr_token, 10),
        ];
    }
}
