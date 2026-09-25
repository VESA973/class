@extends('admin.layout')

@section('title', 'Reservations')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Demandes</h1>
        </div>
        <form class="inline-filter filter-bar" method="GET">
            <select name="request_status" aria-label="Filtrer par suivi" onchange="this.form.submit()">
                <option value="">Tout le suivi</option>
                @foreach (\App\Models\Reservation::REQUEST_STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('request_status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">Toutes les réservations</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Models\Reservation::STATUS_LABELS[$status] ?? $status }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Véhicule</th>
                    <th>Prestation</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Suivi</th>
                    <th>Réservation</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservations as $reservation)
                    <tr>
                        <td>
                            <strong>{{ $reservation->customer_name }}</strong>
                            <span>{{ $reservation->customer_phone }}</span>
                        </td>
                        <td>{{ $reservation->vehicle->name }}</td>
                        <td>{{ $reservation->prestation?->name ?: $reservation->service_type }}</td>
                        <td>{{ $reservation->start_at?->format('d/m/Y H:i') ?? $reservation->start_date->format('d/m/Y') }}<span>{{ $reservation->days }} jour(s)</span></td>
                        <td>{{ number_format($reservation->estimated_total, 0, ',', ' ') }} EUR</td>
                        <td>
                            <span class="tag">{{ $reservation->request_status_label }}</span>
                            @if ($reservation->quotes->isNotEmpty())
                                <span>{{ $reservation->quotes->first()->number }}</span>
                            @endif
                        </td>
                        <td><span class="tag tag-{{ $reservation->status }}">{{ $reservation->status_label }}</span></td>
                        <td><a href="{{ route('admin.reservations.show', $reservation) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">Aucune réservation pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reservations->links() }}
@endsection
