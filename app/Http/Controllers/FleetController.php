<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\View\View;

/** Pages publiques de la flotte : catalogue et fiche vehicule (avec 3D). */
class FleetController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->orderByDesc('daily_price')
            ->get();

        return view('pages.fleet.index', [
            'vehicles' => $vehicles,
            'categories' => $vehicles->pluck('category')->unique()->values(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        abort_unless($vehicle->is_available, 404);

        $others = Vehicle::query()
            ->where('is_available', true)
            ->whereKeyNot($vehicle->id)
            ->orderByRaw('category = ? desc', [$vehicle->category])
            ->orderByDesc('daily_price')
            ->limit(3)
            ->get();

        return view('pages.fleet.show', [
            'vehicle' => $vehicle,
            'others' => $others,
        ]);
    }
}
