<?php

namespace App\Http\Controllers;

use App\Enums\UnitStatus;
use App\Models\EvidenceCategory;
use App\Models\UnitItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvidenceItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = UnitItem::query()
            ->with(['physicalUnit.legalCase', 'physicalUnit.storageLocation', 'physicalUnit.photo'])
            ->search($request->string('q')->toString())
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('status'), fn ($query) => $query->whereHas(
                'physicalUnit',
                fn ($unit) => $unit->where('current_status', $request->string('status'))
            ))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('items.index', [
            'items' => $items,
            'categories' => EvidenceCategory::active()->get(),
            'statuses' => UnitStatus::cases(),
        ]);
    }

    public function show(UnitItem $item): View
    {
        $item->load([
            'physicalUnit.legalCase',
            'physicalUnit.storageLocation',
            'physicalUnit.photo',
            'physicalUnit.loans.outboundPhoto',
            'physicalUnit.loans.inboundPhoto',
            'physicalUnit.items',
        ]);

        return view('items.show', [
            'item' => $item,
            'loans' => $item->physicalUnit?->loans ?? collect(),
        ]);
    }
}
