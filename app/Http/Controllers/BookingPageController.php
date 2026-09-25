<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BookingController;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingPageController extends Controller
{
    public function create(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->where('is_available', true)
            ->orderBy('category')
            ->orderBy('daily_price')
            ->get();

        $initialVehicleId = $request->integer('vehicle') ?: null;

        // Pre-remplissage depuis le module de recherche de l'accueil (?start=&end=&pickup=).
        $prefill = validator($request->only('start', 'end', 'pickup'), [
            'start' => ['nullable', 'date_format:'.ReservationAvailability::DATETIME_FORMAT],
            'end' => ['nullable', 'date_format:'.ReservationAvailability::DATETIME_FORMAT, 'after:start'],
            'pickup' => ['nullable', 'string', 'max:180'],
        ]);
        $initial = $prefill->fails() ? [] : $prefill->validated();

        return view('pages.booking', [
            'props' => [
                'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => BookingController::vehiclePayload($vehicle))->values(),
                'initialVehicleId' => $vehicles->contains('id', $initialVehicleId) ? $initialVehicleId : null,
                'initialStart' => $initial['start'] ?? null,
                'initialEnd' => isset($initial['start']) ? ($initial['end'] ?? null) : null,
                'initialPickup' => $initial['pickup'] ?? null,
                'csrfToken' => csrf_token(),
                'contactPhone' => config('home.contact.phone'),
                'urls' => [
                    'store' => route('api.reservations.store'),
                    'bookedPeriods' => route('api.vehicles.booked-periods', ['vehicle' => '__VEHICLE__']),
                    'available' => route('api.vehicles.available'),
                    'home' => route('home'),
                    'privacy' => \App\Models\LegalPage::query()->where('key', 'privacy')->where('is_published', true)->value('slug') !== null
                        ? url('/'.\App\Models\LegalPage::query()->where('key', 'privacy')->value('slug'))
                        : null,
                ],
            ],
        ]);
    }
}
