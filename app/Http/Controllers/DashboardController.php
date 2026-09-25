<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Illuminate\View\View;

/** Tableau de bord de l'administration. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $now = ReservationAvailability::now();

        return view('admin.dashboard', [
            'stats' => [
                'vehicles' => Vehicle::count(),
                'vehiclesAvailable' => Vehicle::where('is_available', true)->count(),
                'pending' => Reservation::where('status', 'pending')->count(),
                'upcoming' => Reservation::whereIn('status', ['pending', 'confirmed'])
                    ->whereBetween('start_at', [$now, $now->copy()->addDays(7)])
                    ->count(),
                'prestations' => Prestation::where('is_active', true)->count(),
                'quotesPending' => \App\Models\Quote::where('status', 'sent')->count(),
                'quotesPendingTotal' => \App\Models\Quote::where('status', 'sent')->sum('total_ttc'),
            ],
            // Demandes recentes (une requete pour les vehicules : pas de N+1)
            'recent' => Reservation::with('vehicle:id,name')->latest()->limit(6)->get(),
            'nextDepartures' => Reservation::with('vehicle:id,name')
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_at', '>=', $now)
                ->orderBy('start_at')
                ->limit(5)
                ->get(),
            'maintenance' => (bool) app(\App\Services\Settings::class)->get('maintenance.enabled', false),
        ]);
    }
}
