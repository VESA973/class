<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'pickup_location' => ['required', 'string', 'max:180'],
            'service_type' => ['required', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $vehicle = Vehicle::query()
            ->where('is_available', true)
            ->findOrFail($validated['vehicle_id']);

        $validated['estimated_total'] = $vehicle->daily_price * (int) $validated['days'];
        $validated['end_date'] = Carbon::parse($validated['start_date'])
            ->addDays((int) $validated['days'] - 1)
            ->toDateString();
        $validated['status'] = 'pending';

        Reservation::create($validated);

        return redirect()
            ->route('home')
            ->with('reservation_success', 'Votre demande de reservation a ete envoyee. Nous vous recontactons rapidement.');
    }

    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->with('vehicle')
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
            'reservation' => $reservation->load('vehicle'),
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
