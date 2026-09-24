<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleAvailabilityController extends Controller
{
    public function __construct(private readonly ReservationAvailability $availability)
    {
    }

    /** Periodes deja reservees d'un vehicule (sans donnees client). */
    public function bookedPeriods(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->is_available, 404);

        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after:from'],
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : ReservationAvailability::now()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->startOfDay()
            : $from->copy()->addMonths(12);

        // Fenetre maximale de 18 mois pour garder la reponse legere.
        if ($to->gt($from->copy()->addMonths(18))) {
            $to = $from->copy()->addMonths(18);
        }

        $periods = array_map(
            fn (array $period) => ReservationAvailability::toArray($period['start_at'], $period['end_at']),
            $this->availability->bookedPeriods($vehicle->id, $from, $to),
        );

        return response()->json([
            'vehicle_id' => $vehicle->id,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'periods' => $periods,
        ]);
    }

    /** Vehicules libres sur une periode (module de recherche). */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'start_at' => ['required', 'date_format:'.ReservationAvailability::DATETIME_FORMAT],
            'end_at' => ['required', 'date_format:'.ReservationAvailability::DATETIME_FORMAT, 'after:start_at'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);

        $start = Carbon::parse($request->input('start_at'));
        $end = Carbon::parse($request->input('end_at'));

        $vehicles = Vehicle::query()
            ->where('is_available', true)
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->whereDoesntHave('reservations', fn ($query) => $query
                ->whereIn('status', ReservationAvailability::BLOCKING_STATUSES)
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start))
            ->orderBy('category')
            ->orderBy('daily_price')
            ->get();

        return response()->json([
            'start_at' => $start->format(ReservationAvailability::DATETIME_FORMAT),
            'end_at' => $end->format(ReservationAvailability::DATETIME_FORMAT),
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => BookingController::vehiclePayload($vehicle))->values(),
        ]);
    }
}
