<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use App\Models\Vehicle;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $vehicles = Vehicle::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->orderBy('daily_price')
            ->get();

        return view('welcome', [
            'prestations' => Prestation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'vehicles' => $vehicles,
        ]);
    }
}
