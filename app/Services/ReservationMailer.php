<?php

namespace App\Services;

use App\Mail\NewReservationAdminMail;
use App\Mail\ReservationReceivedMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails envoyes apres une reservation. Un echec d'envoi est journalise et
 * n'interrompt jamais la reservation, deja enregistree a ce stade.
 */
class ReservationMailer
{
    public function sendNewReservation(Reservation $reservation): void
    {
        $reservation->loadMissing('vehicle');

        $this->notifyAdmin($reservation);

        if (config('booking.send_customer_confirmation') && $reservation->customer_email) {
            $this->send($reservation, $reservation->customer_email, new ReservationReceivedMail($reservation), 'client');
        }
    }

    private function notifyAdmin(Reservation $reservation): void
    {
        $adminEmail = config('booking.admin_email');

        if (! $adminEmail) {
            Log::warning('ADMIN_EMAIL non configure : email administrateur non envoye.', ['reservation_id' => $reservation->id]);

            return;
        }

        $this->send($reservation, $adminEmail, new NewReservationAdminMail($reservation), 'administrateur');
    }

    private function send(Reservation $reservation, string $to, \Illuminate\Mail\Mailable $mail, string $recipientLabel): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $exception) {
            Log::error("Echec d'envoi de l'email {$recipientLabel} pour la reservation.", [
                'reservation_id' => $reservation->id,
                'to' => $to,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
