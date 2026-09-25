<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Planning administrateur : calendrier des reservations. */
class PlanningController extends Controller
{
    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée',
        'completed' => 'Terminée',
    ];

    public function index(): View
    {
        return view('admin.planning.index', [
            'props' => [
                'vehicles' => Vehicle::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'is_available'])
                    ->map(fn (Vehicle $vehicle) => ['id' => $vehicle->id, 'name' => $vehicle->name])
                    ->values(),
                'statusLabels' => self::STATUS_LABELS,
                'csrfToken' => csrf_token(),
                'urls' => [
                    'events' => route('admin.planning.events'),
                    'status' => route('admin.planning.status', ['reservation' => '__ID__']),
                ],
            ],
        ]);
    }

    /** Reservations qui touchent la periode affichee par le calendrier. */
    public function events(Request $request): JsonResponse
    {
        $format = ReservationAvailability::DATETIME_FORMAT;

        $request->validate([
            'start' => ['required', 'date_format:'.$format],
            'end' => ['required', 'date_format:'.$format, 'after:start'],
            'vehicle_id' => ['nullable', 'integer'],
        ]);

        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));

        // Garde-fou : 6 semaines couvrent la vue mois, on laisse de la marge.
        if ($start->diffInDays($end) > 100) {
            $end = $start->copy()->addDays(100);
        }

        $reservations = Reservation::query()
            ->with('vehicle:id,name')
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($request->filled('vehicle_id'), fn ($query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->orderBy('start_at')
            ->get();

        return response()->json($reservations->map(fn (Reservation $reservation) => $this->event($reservation))->values());
    }

    public function updateStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'cancelled'])],
        ]);

        $reservation->update(['status' => $validated['status']]);

        return response()->json([
            'message' => $validated['status'] === 'confirmed' ? 'Réservation confirmée.' : 'Réservation annulée.',
            'event' => $this->event($reservation->load('vehicle:id,name')),
        ]);
    }

    /** @return array<string, mixed> */
    private function event(Reservation $reservation): array
    {
        return [
            'id' => (string) $reservation->id,
            'title' => ($reservation->vehicle?->name ?? 'Véhicule supprimé').' · '.$reservation->customer_name,
            ...ReservationAvailability::toArray($reservation->start_at, $reservation->end_at),
            'extendedProps' => [
                'status' => $reservation->status,
                'vehicleId' => $reservation->vehicle_id,
                'vehicleName' => $reservation->vehicle?->name,
                'customerName' => $reservation->customer_name,
                'customerEmail' => $reservation->customer_email,
                'customerPhone' => $reservation->customer_phone,
                'pickupLocation' => $reservation->pickup_location,
                'destination' => $reservation->destination,
                'passengers' => $reservation->passengers,
                'serviceType' => $reservation->service_type,
                'days' => $reservation->days,
                'estimatedTotal' => $reservation->estimated_total,
                'message' => $reservation->message,
                'createdAt' => $reservation->created_at?->format('d/m/Y à H:i'),
                'adminUrl' => route('admin.reservations.show', $reservation),
            ],
        ];
    }
}
