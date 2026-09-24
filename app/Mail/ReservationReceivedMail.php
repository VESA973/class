<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Votre demande de réservation #%d - %s', $this->reservation->id, config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reservations.customer',
            with: [
                'contactPhone' => config('booking.contact_phone'),
            ],
        );
    }
}
