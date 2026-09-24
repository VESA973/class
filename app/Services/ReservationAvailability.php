<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Disponibilite des vehicules.
 *
 * Les heures sont stockees en heure locale "murale" (Europe/Paris) sans
 * conversion. Une periode est semi-ouverte [start_at, end_at[ : deux
 * reservations se chevauchent si debut_existant < fin_demandee ET
 * fin_existante > debut_demande.
 */
class ReservationAvailability
{
    public const TIMEZONE = 'Europe/Paris';

    /** Statuts qui immobilisent le vehicule (une reservation annulee libere le creneau). */
    public const BLOCKING_STATUSES = ['pending', 'confirmed', 'completed'];

    public const DATETIME_FORMAT = 'Y-m-d\TH:i';

    /** Horaires proposes au client (creneaux de 30 minutes), identiques au formulaire. */
    public const OPENING_MINUTES = 7 * 60;

    public const CLOSING_MINUTES = 22 * 60;

    public const SLOT_MINUTES = 30;

    /** Heure actuelle a Paris, exprimee dans le meme referentiel que les colonnes start_at / end_at. */
    public static function now(): Carbon
    {
        return Carbon::parse(Carbon::now(self::TIMEZONE)->format('Y-m-d H:i:s'));
    }

    /** @return Collection<int, Reservation> */
    public function conflicts(int $vehicleId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreReservationId = null): Collection
    {
        return Reservation::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->orderBy('start_at')
            ->get();
    }

    public function isAvailable(int $vehicleId, CarbonInterface $start, CarbonInterface $end): bool
    {
        return $this->conflicts($vehicleId, $start, $end)->isEmpty();
    }

    /**
     * Periodes occupees entre deux dates, fusionnees quand elles se touchent.
     * Ne contient aucune donnee client.
     *
     * @return list<array{start_at: Carbon, end_at: Carbon}>
     */
    public function bookedPeriods(int $vehicleId, CarbonInterface $from, CarbonInterface $to): array
    {
        $reservations = Reservation::query()
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('start_at', '<', $to)
            ->where('end_at', '>', $from)
            ->orderBy('start_at')
            ->get(['start_at', 'end_at']);

        return $this->merge($reservations);
    }

    /**
     * Prochains creneaux libres de meme duree, a partir du debut demande.
     *
     * @return list<array{start_at: Carbon, end_at: Carbon}>
     */
    public function suggestions(int $vehicleId, CarbonInterface $start, CarbonInterface $end, int $limit = 3): array
    {
        $duration = (int) abs($start->diffInSeconds($end));
        $cursor = Carbon::instance($start);

        $busy = $this->merge(
            Reservation::query()
                ->where('vehicle_id', $vehicleId)
                ->whereIn('status', self::BLOCKING_STATUSES)
                ->where('end_at', '>', $start)
                ->orderBy('start_at')
                ->get(['start_at', 'end_at'])
        );

        $slots = [];

        foreach ($busy as $period) {
            $candidate = $this->withinOpeningHours($cursor, $duration);

            if ($candidate->copy()->addSeconds($duration)->lte($period['start_at'])) {
                $slots[] = ['start_at' => $candidate, 'end_at' => $candidate->copy()->addSeconds($duration)];

                if (count($slots) >= $limit) {
                    return $slots;
                }
            }

            if ($period['end_at']->gt($cursor)) {
                $cursor = $period['end_at']->copy();
            }
        }

        $candidate = $this->withinOpeningHours($cursor, $duration);
        $slots[] = ['start_at' => $candidate, 'end_at' => $candidate->copy()->addSeconds($duration)];

        return $slots;
    }

    /**
     * Premier creneau >= $from dont le depart et le retour tombent dans les
     * horaires proposes (07:00-22:00, pas de 30 minutes).
     */
    private function withinOpeningHours(Carbon $from, int $durationSeconds): Carbon
    {
        $start = $from->copy()->second(0);
        $overflow = $start->minute % self::SLOT_MINUTES;

        if ($overflow > 0) {
            $start->addMinutes(self::SLOT_MINUTES - $overflow);
        }

        // Quelques jours suffisent toujours ; la borne evite une boucle infinie.
        for ($attempt = 0; $attempt < 7; $attempt++) {
            $minutes = $start->hour * 60 + $start->minute;

            if ($minutes < self::OPENING_MINUTES) {
                $start->startOfDay()->addMinutes(self::OPENING_MINUTES);
            } elseif ($minutes > self::CLOSING_MINUTES) {
                $start->addDay()->startOfDay()->addMinutes(self::OPENING_MINUTES);
            }

            $end = $start->copy()->addSeconds($durationSeconds);
            $endMinutes = $end->hour * 60 + $end->minute;

            if ($endMinutes >= self::OPENING_MINUTES && $endMinutes <= self::CLOSING_MINUTES) {
                return $start;
            }

            // Retour hors horaires : on decale le depart pour que le retour tombe a l'ouverture.
            $shift = $endMinutes < self::OPENING_MINUTES
                ? self::OPENING_MINUTES - $endMinutes
                : 24 * 60 - $endMinutes + self::OPENING_MINUTES;
            $start->addMinutes($shift);
        }

        return $start;
    }

    /**
     * "du 01/10/2026 a 09:00 au 03/10/2026 a 10:00", ou pour des jours entiers
     * (minuit a minuit) "du 01/10/2026 au 02/10/2026" / "le 01/10/2026".
     */
    public static function describe(CarbonInterface $start, CarbonInterface $end): string
    {
        if ($start->format('H:i') === '00:00' && $end->format('H:i') === '00:00') {
            $lastDay = $end->copy()->subDay();

            return $lastDay->isSameDay($start)
                ? 'le '.$start->format('d/m/Y')
                : sprintf('du %s au %s', $start->format('d/m/Y'), $lastDay->format('d/m/Y'));
        }

        return sprintf(
            'du %s à %s au %s à %s',
            $start->format('d/m/Y'),
            $start->format('H:i'),
            $end->format('d/m/Y'),
            $end->format('H:i'),
        );
    }

    /** @return array{start_at: string, end_at: string} */
    public static function toArray(CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'start_at' => $start->format(self::DATETIME_FORMAT),
            'end_at' => $end->format(self::DATETIME_FORMAT),
        ];
    }

    /**
     * @param  Collection<int, Reservation>  $reservations  triees par start_at
     * @return list<array{start_at: Carbon, end_at: Carbon}>
     */
    private function merge(Collection $reservations): array
    {
        $merged = [];

        foreach ($reservations as $reservation) {
            $last = array_key_last($merged);

            if ($last !== null && $reservation->start_at->lte($merged[$last]['end_at'])) {
                if ($reservation->end_at->gt($merged[$last]['end_at'])) {
                    $merged[$last]['end_at'] = $reservation->end_at->copy();
                }

                continue;
            }

            $merged[] = ['start_at' => $reservation->start_at->copy(), 'end_at' => $reservation->end_at->copy()];
        }

        return $merged;
    }
}
