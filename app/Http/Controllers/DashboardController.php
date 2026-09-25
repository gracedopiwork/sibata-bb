<?php

namespace App\Http\Controllers;

use App\Enums\MutationType;
use App\Enums\UnitStatus;
use App\Models\LegalCase;
use App\Models\Mutation;
use App\Models\PhysicalUnit;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $statusCounts = PhysicalUnit::query()
            ->selectRaw('current_status, COUNT(*) as total')
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        $stats = [
            'cases' => LegalCase::query()->count(),
            'units' => PhysicalUnit::query()->count(),
            'gudang' => (int) ($statusCounts[UnitStatus::TersimpanGudang->value] ?? 0),
            'pinjam' => (int) ($statusCounts[UnitStatus::DipinjamSidang->value] ?? 0),
            'selesai' => (int) ($statusCounts[UnitStatus::Selesai->value] ?? 0),
            'unprinted' => PhysicalUnit::query()->where('is_printed', false)->count(),
        ];

        $recentMutations = Mutation::query()
            ->with('physicalUnit.legalCase')
            ->latest('id')
            ->limit(8)
            ->get();

        $overdue = PhysicalUnit::query()
            ->with(['legalCase', 'mutations'])
            ->where('current_status', UnitStatus::DipinjamSidang)
            ->get()
            ->filter(function (PhysicalUnit $unit) {
                $loan = $unit->mutations->first(
                    fn (Mutation $mutation) => $mutation->mutation_type === MutationType::PinjamSidang
                );

                $courtDate = $loan?->court_date;

                return $courtDate !== null && $courtDate->lt(now()->startOfDay());
            })
            ->take(6);

        return view('dashboard', compact('stats', 'recentMutations', 'overdue'));
    }
}
