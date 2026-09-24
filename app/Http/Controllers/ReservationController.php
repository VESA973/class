<?php

namespace App\Http\Controllers;

use App\Models\Prestation;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Services\ReservationAvailability;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function store(Request $request, ReservationAvailability $availability): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'prestation_id' => ['nullable', 'exists:prestations,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'pickup_location' => ['required', 'string', 'max:180'],
            'service_type' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $vehicle = Vehicle::query()
            ->where('is_available', true)
            ->findOrFail($validated['vehicle_id']);

        if (! empty($validated['prestation_id'])) {
            $prestation = Prestation::query()
                ->where('is_active', true)
                ->findOrFail($validated['prestation_id']);

            $validated['service_type'] = $prestation->name;
        } else {
            $validated['service_type'] = $validated['service_type'] ?? 'Sans chauffeur';
        }

        $validated['estimated_total'] = $vehicle->daily_price * (int) $validated['days'];
        $validated['end_date'] = Carbon::parse($validated['start_date'])
            ->addDays((int) $validated['days'] - 1)
            ->toDateString();
        $validated['status'] = 'pending';

        // Jours entiers : du premier jour 00:00 au lendemain du dernier jour 00:00.
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay()->addDay();

        // Verrou sur le vehicule pour que deux demandes simultanees ne reservent pas le meme creneau.
        $conflict = DB::transaction(function () use ($vehicle, $start, $end, $availability, $validated) {
            Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->first();

            $conflict = $availability->conflicts($vehicle->id, $start, $end)->first();

            if (! $conflict) {
                Reservation::create($validated);
            }

            return $conflict;
        });

        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_date' => 'Ce véhicule est déjà réservé '
                        .ReservationAvailability::describe($conflict->start_at, $conflict->end_at)
                        .', veuillez choisir d\'autres dates.',
                ]);
        }

        return redirect()
            ->route('home')
            ->with('reservation_success', 'Votre demande de reservation a ete envoyee. Nous vous recontactons rapidement.');
    }

    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->with(['prestation', 'vehicle'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'statuses' => Reservation::STATUSES,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        return view('admin.reservations.show', [
            'reservation' => $reservation->load(['prestation', 'vehicle']),
            'statuses' => Reservation::STATUSES,
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Reservation::STATUSES)],
        ]);

        $reservation->update($validated);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('status', 'Statut de reservation mis a jour.');
    }
}
