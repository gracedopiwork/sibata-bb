<?php

namespace App\Http\Controllers;

use App\Models\PhysicalUnit;
use Illuminate\View\View;

class PublicUnitController extends Controller
{
    public function show(string $unit_code): View
    {
        $unit = PhysicalUnit::query()
            ->with(['legalCase', 'items'])
            ->where('unit_code', strtoupper($unit_code))
            ->firstOrFail();

        return view('public.unit', compact('unit'));
    }
}
