<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Models\ReservationEvent;
use App\Services\QuoteService;

/**
 * Nouvelle demande : trace dans l'historique, puis envoi automatique du devis
 * UNIQUEMENT si l'option est activee (desactivee par defaut, Admin > Devis > Reglages).
 */
class HandleReservationCreated
{
    public function __construct(private readonly QuoteService $quotes)
    {
    }

    public function handle(ReservationCreated $event): void
    {
        ReservationEvent::record($event->reservation, 'created', 'Demande reçue depuis le site.');

        $this->quotes->handleNewReservation($event->reservation);
    }
}
