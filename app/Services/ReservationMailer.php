<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

/**
 * Emails envoyes apres une reservation, a partir des modeles editables
 * (Admin > Emails > Modeles). Un echec d'envoi est journalise (historique + log)
 * et n'interrompt jamais la reservation, deja enregistree a ce stade.
 */
class ReservationMailer
{
    public function __construct(
        private readonly EmailService $emails,
        private readonly MailSettings $mailSettings,
    ) {
    }

    public function sendNewReservation(Reservation $reservation): void
    {
        $reservation->loadMissing('vehicle');
        $variables = self::variables($reservation);

        $adminEmail = $this->mailSettings->adminEmail();

        if ($adminEmail) {
            $this->emails->sendTemplate('reservation_admin_notification', $adminEmail, $variables, [
                // "Repondre" ecrit directement au client.
                'reply_to' => $reservation->customer_email,
                'reply_to_name' => $reservation->customer_name,
                'reservation_id' => $reservation->id,
            ]);
        } else {
            Log::warning('Adresse administrateur non configurée : notification admin non envoyée.', ['reservation_id' => $reservation->id]);
        }

        if (config('booking.send_customer_confirmation') && $reservation->customer_email) {
            $this->emails->sendTemplate('reservation_received_customer', $reservation->customer_email, $variables, [
                'reservation_id' => $reservation->id,
            ]);
        }
    }

    /**
     * Variables disponibles dans les modeles lies a une reservation.
     *
     * @return array<string, string>
     */
    public static function variables(Reservation $reservation): array
    {
        $format = fn ($date) => $date?->format('d/m/Y à H:i') ?? '—';

        return [
            'numero_reservation' => (string) $reservation->id,
            'nom_client' => $reservation->customer_name,
            'email_client' => $reservation->customer_email ?: '—',
            'telephone_client' => $reservation->customer_phone,
            'message_client' => $reservation->message ?: '—',
            'vehicule' => $reservation->vehicle?->name ?? '—',
            'date_depart' => $format($reservation->start_at ?? $reservation->start_date),
            'date_retour' => $format($reservation->end_at ?? $reservation->end_date),
            'lieu_depart' => $reservation->pickup_location,
            'destination' => $reservation->destination ?: 'Non précisée',
            'passagers' => $reservation->passengers ? (string) $reservation->passengers : 'Non précisé',
            'montant_estime' => number_format((int) $reservation->estimated_total, 0, ',', ' ').' €',
            'lien_admin' => route('admin.reservations.show', $reservation),
        ];
    }
}
