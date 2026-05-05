@extends('admin.layout')

@section('title', 'Reservations')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes clients</p>
            <h1>Reservations</h1>
        </div>
        <form class="inline-filter" method="GET">
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Vehicule</th>
                    <th>Prestation</th>
                    <th>Dates</th>
                    <th>Total</th>
                    <th>Statut</th>
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
                        <td>{{ $reservation->start_date->format('d/m/Y') }} - {{ $reservation->days }} jour(s)</td>
                        <td>{{ number_format($reservation->estimated_total, 0, ',', ' ') }} EUR</td>
                        <td><span class="tag">{{ ucfirst($reservation->status) }}</span></td>
                        <td><a href="{{ route('admin.reservations.show', $reservation) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Aucune reservation pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reservations->links() }}
@endsection
