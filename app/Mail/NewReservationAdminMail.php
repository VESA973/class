<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReservationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Nouvelle réservation #%d - %s - %s',
                $this->reservation->id,
                $this->reservation->vehicle->name,
                $this->reservation->start_at->format('d/m/Y H:i'),
            ),
            // "Repondre" ecrit directement au client.
            replyTo: $this->reservation->customer_email
                ? [new Address($this->reservation->customer_email, $this->reservation->customer_name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reservations.admin',
            with: [
                'adminUrl' => route('admin.reservations.show', $this->reservation),
            ],
        );
    }
}
