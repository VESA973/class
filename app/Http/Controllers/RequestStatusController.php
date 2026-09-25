<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Suivi commercial d'une demande (Nouvelle, En cours, Devis envoye, Accepte, Refuse, Archivee). */
class RequestStatusController extends Controller
{
    public function __invoke(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate(['request_status' => ['required', Rule::in(array_keys(Reservation::REQUEST_STATUSES))]]);
        $previous = $reservation->request_status_label;
        $reservation->update($data);

        if ($previous !== $reservation->request_status_label) {
            ReservationEvent::record($reservation, 'request_status', "Suivi : {$previous} → {$reservation->request_status_label}.");
        }

        return back()->with('status', 'Suivi de la demande mis à jour.');
    }
}
