<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Foundation\Events\Dispatchable;

/** Une nouvelle demande (reservation) vient d'etre enregistree depuis le site. */
class ReservationCreated
{
    use Dispatchable;

    public function __construct(public Reservation $reservation)
    {
    }
}
