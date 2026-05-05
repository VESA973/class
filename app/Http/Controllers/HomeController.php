<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use App\Models\Vehicle;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'prestations' => $this->activePrestations(),
            'vehicles' => $this->availableVehicles(),
        ]);
    }

    public function vehicles(): View
    {
        return view('pages.vehicles', [
            'vehicles' => $this->availableVehicles(),
        ]);
    }

    public function prestations(): View
    {
        return view('pages.prestations', [
            'prestations' => $this->activePrestations(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    private function availableVehicles()
    {
        return Vehicle::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->orderBy('daily_price')
            ->get();
    }

    private function activePrestations()
    {
        return Prestation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
