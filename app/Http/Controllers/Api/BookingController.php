<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use App\Services\ReservationMailer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function Illuminate\Support\defer;

class BookingController extends Controller
{
    private const MAX_DAYS = 90;

    public function __construct(
        private readonly ReservationAvailability $availability,
        private readonly ReservationMailer $mailer,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $format = ReservationAvailability::DATETIME_FORMAT;

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'start_at' => ['required', 'date_format:'.$format],
            'end_at' => ['required', 'date_format:'.$format, 'after:start_at'],
            'pickup_location' => ['required', 'string', 'max:180'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['required', 'string', 'regex:/^[+0-9 ().-]{6,40}$/'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'vehicle_id.required' => 'Choisissez un véhicule.',
            'vehicle_id.exists' => 'Ce véhicule n\'existe pas.',
            'start_at.required' => 'Indiquez la date et l\'heure de départ.',
            'start_at.date_format' => 'Date de départ invalide.',
            'end_at.required' => 'Indiquez la date et l\'heure de retour.',
            'end_at.date_format' => 'Date de retour invalide.',
            'end_at.after' => 'Le retour doit être après le départ.',
            'pickup_location.required' => 'Indiquez le lieu de prise en charge.',
            'customer_name.required' => 'Indiquez votre nom.',
            'customer_email.required' => 'Indiquez votre adresse email.',
            'customer_email.email' => 'Adresse email invalide.',
            'customer_phone.required' => 'Indiquez votre numéro de téléphone.',
            'customer_phone.regex' => 'Numéro de téléphone invalide.',
            '*.max' => 'Ce champ est trop long.',
        ]);

        $start = Carbon::parse($validated['start_at']);
        $end = Carbon::parse($validated['end_at']);

        if ($start->lt(ReservationAvailability::now())) {
            throw ValidationException::withMessages(['start_at' => 'Le départ doit être dans le futur.']);
        }

        $days = max(1, (int) ceil($start->diffInMinutes($end) / 1440));

        if ($days > self::MAX_DAYS) {
            throw ValidationException::withMessages(['end_at' => 'La location ne peut pas dépasser '.self::MAX_DAYS.' jours.']);
        }

        // Verrou sur le vehicule : deux demandes simultanees sur la meme voiture
        // sont traitees l'une apres l'autre, la seconde voit le conflit.
        $result = DB::transaction(function () use ($validated, $start, $end, $days) {
            $vehicle = Vehicle::query()
                ->where('is_available', true)
                ->lockForUpdate()
                ->find($validated['vehicle_id']);

            if (! $vehicle) {
                return ['unavailable' => true];
            }

            $conflicts = $this->availability->conflicts($vehicle->id, $start, $end);

            if ($conflicts->isNotEmpty()) {
                return ['vehicle' => $vehicle, 'conflicts' => $conflicts];
            }

            $reservation = Reservation::create([
                'vehicle_id' => $vehicle->id,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->copy()->subSecond()->toDateString(),
                'start_at' => $start,
                'end_at' => $end,
                'days' => $days,
                'pickup_location' => $validated['pickup_location'],
                'service_type' => $vehicle->with_chauffeur ? 'Avec chauffeur' : 'Sans chauffeur',
                'estimated_total' => $vehicle->daily_price * $days,
                'status' => 'pending',
                'message' => $validated['message'] ?? null,
            ]);

            return ['vehicle' => $vehicle, 'reservation' => $reservation];
        });

        if (isset($result['unavailable'])) {
            throw ValidationException::withMessages(['vehicle_id' => 'Ce véhicule n\'est plus proposé à la location.']);
        }

        if (isset($result['conflicts'])) {
            $first = $result['conflicts']->first();

            return response()->json([
                'message' => 'Ce véhicule est déjà réservé '
                    .ReservationAvailability::describe($first->start_at, $first->end_at)
                    .', veuillez choisir d\'autres dates.',
                'conflicts' => $result['conflicts']
                    ->map(fn (Reservation $conflict) => ReservationAvailability::toArray($conflict->start_at, $conflict->end_at))
                    ->values(),
                'suggestions' => array_map(
                    fn (array $slot) => ReservationAvailability::toArray($slot['start_at'], $slot['end_at']),
                    $this->availability->suggestions($result['vehicle']->id, $start, $end),
                ),
            ], 409);
        }

        /** @var Reservation $reservation */
        $reservation = $result['reservation'];

        // Envoi apres la reponse HTTP : le client n'attend pas le serveur mail,
        // et un echec d'envoi est journalise sans annuler la reservation.
        defer(fn () => $this->mailer->sendNewReservation($reservation));

        return response()->json([
            'message' => 'Votre demande de réservation a bien été envoyée.',
            'reservation' => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'days' => $reservation->days,
                'estimated_total' => $reservation->estimated_total,
                'pickup_location' => $reservation->pickup_location,
                'customer_name' => $reservation->customer_name,
                'customer_email' => $reservation->customer_email,
                ...ReservationAvailability::toArray($reservation->start_at, $reservation->end_at),
                'vehicle' => self::vehiclePayload($result['vehicle']),
            ],
        ], 201);
    }

    /** @return array<string, mixed> */
    public static function vehiclePayload(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'category' => $vehicle->category,
            'daily_price' => $vehicle->daily_price,
            'image' => $vehicle->display_image,
            'fuel_type' => $vehicle->fuel_type,
            'transmission' => $vehicle->transmission,
            'horsepower' => $vehicle->horsepower,
            'with_chauffeur' => $vehicle->with_chauffeur,
        ];
    }
}
