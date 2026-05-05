<?php

namespace App\Http\Controllers;

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
            'vehicles' => $vehicles,
        ]);
    }
}
