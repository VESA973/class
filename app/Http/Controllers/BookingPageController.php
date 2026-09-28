<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BookingController;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use App\Services\SiteContent;
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

        $serviceTypes = app(SiteContent::class)->serviceTypes();

        // Pre-remplissage depuis le module de recherche de l'accueil (memes champs).
        // Chaque valeur est verifiee separement : une valeur invalide est simplement ignoree.
        $rules = [
            'start' => ['date_format:'.ReservationAvailability::DATETIME_FORMAT],
            'end' => ['date_format:'.ReservationAvailability::DATETIME_FORMAT],
            'pickup' => ['string', 'max:180'],
            'destination' => ['string', 'max:255'],
            'passengers' => ['integer', 'min:1', 'max:9'],
            'prestation' => ['string', \Illuminate\Validation\Rule::in($serviceTypes)],
        ];
        $initial = [];

        foreach ($rules as $key => $rule) {
            if ($request->filled($key) && validator([$key => $request->query($key)], [$key => $rule])->passes()) {
                $initial[$key] = $request->query($key);
            }
        }

        if (isset($initial['start'], $initial['end']) && $initial['end'] <= $initial['start']) {
            unset($initial['end']);
        }

        return view('pages.booking', [
            'props' => [
                'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => BookingController::vehiclePayload($vehicle))->values(),
                'initialVehicleId' => $vehicles->contains('id', $initialVehicleId) ? $initialVehicleId : null,
                'initialStart' => $initial['start'] ?? null,
                'initialEnd' => isset($initial['start']) ? ($initial['end'] ?? null) : null,
                'initialPickup' => $initial['pickup'] ?? null,
                'initialDestination' => $initial['destination'] ?? null,
                'initialPassengers' => isset($initial['passengers']) ? (int) $initial['passengers'] : null,
                'initialPrestation' => $initial['prestation'] ?? null,
                'serviceTypes' => $serviceTypes,
                'addressTerritory' => config('home.contact.address_territory'),
                'csrfToken' => csrf_token(),
                'contactPhone' => config('home.contact.phone'),
                'phoneCountryCode' => config('home.contact.country_code'),
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
