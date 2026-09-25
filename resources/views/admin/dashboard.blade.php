@extends('admin.layout')

@section('title', 'Tableau de bord')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ now(config('app.local_timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
            <h1>Bonjour {{ auth()->user()->name }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.planning.index') }}"><x-icon name="calendar-days" style="width:18px;height:18px" /> Ouvrir le planning</a>
    </div>

    <div class="stat-grid">
        <a class="stat-card" href="{{ route('admin.vehicles.index') }}">
            <span class="stat-card-head">Véhicules <x-icon name="car-front" /></span>
            <span class="stat-value">{{ $stats['vehicles'] }}</span>
            <span class="stat-hint">{{ $stats['vehiclesAvailable'] }} disponible(s) à la location</span>
        </a>
        <a class="stat-card" href="{{ route('admin.reservations.index', ['status' => 'pending']) }}">
            <span class="stat-card-head">Demandes en attente <x-icon name="bell" /></span>
            <span class="stat-value">{{ $stats['pending'] }}</span>
            <span class="stat-hint">Réservations à confirmer ou à chiffrer</span>
        </a>
        <a class="stat-card" href="{{ route('admin.quotes.index', ['status' => 'sent']) }}">
            <span class="stat-card-head">Devis en attente <x-icon name="file-text" /></span>
            <span class="stat-value">{{ $stats['quotesPending'] }}</span>
            <span class="stat-hint">{{ \App\Models\Quote::money($stats['quotesPendingTotal']) }} TTC en attente de réponse</span>
        </a>
        <a class="stat-card" href="{{ route('admin.planning.index') }}">
            <span class="stat-card-head">Départs sous 7 jours <x-icon name="calendar-clock" /></span>
            <span class="stat-value">{{ $stats['upcoming'] }}</span>
            <span class="stat-hint">En attente ou confirmés</span>
        </a>
        <a class="stat-card" href="{{ route('admin.parameters.edit') }}">
            <span class="stat-card-head">Mode maintenance <x-icon name="wrench" /></span>
            <span class="stat-value" style="font-size:22px">
                @if ($maintenance)
                    <span class="tag tag-pending" style="font-size:14px">Activé</span>
                @else
                    <span class="tag tag-success" style="font-size:14px">Site en ligne</span>
                @endif
            </span>
            <span class="stat-hint">{{ $maintenance ? 'Les visiteurs voient la page de maintenance' : 'Paramètres › Favicon & maintenance' }}</span>
        </a>
    </div>

    <div class="dashboard-grid">
        <section class="panel-card" aria-labelledby="recent-title">
            <div class="panel-card-head">
                <h2 id="recent-title">Demandes récentes</h2>
                <a href="{{ route('admin.reservations.index') }}">Tout voir →</a>
            </div>
            @if ($recent->isEmpty())
                <p class="empty-state">Aucune demande pour le moment. Les réservations faites sur le site apparaîtront ici.</p>
            @else
                <div class="table-card" style="border:0;border-radius:0 0 16px 16px">
                    <table>
                        <thead>
                            <tr><th>Client</th><th>Véhicule</th><th>Départ</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $reservation)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.reservations.show', $reservation) }}"><strong>{{ $reservation->customer_name }}</strong></a>
                                        <span>Reçue {{ $reservation->created_at?->locale('fr')->diffForHumans() }}</span>
                                    </td>
                                    <td>{{ $reservation->vehicle?->name ?? '—' }}</td>
                                    <td>{{ $reservation->start_at?->format('d/m/Y H:i') ?? $reservation->start_date->format('d/m/Y') }}</td>
                                    <td><span class="tag tag-{{ $reservation->status }}">{{ $reservation->status_label }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel-card" aria-labelledby="next-title">
            <div class="panel-card-head">
                <h2 id="next-title">Prochains départs</h2>
                <a href="{{ route('admin.planning.index') }}">Planning →</a>
            </div>
            @if ($nextDepartures->isEmpty())
                <p class="empty-state">Aucun départ prévu.</p>
            @else
                <ul class="panel-list">
                    @foreach ($nextDepartures as $reservation)
                        <li>
                            <span>
                                <strong>{{ $reservation->start_at->locale('fr')->isoFormat('ddd D MMM, HH:mm') }}</strong>
                                <small>{{ $reservation->vehicle?->name }} · {{ $reservation->customer_name }}</small>
                            </span>
                            <span class="tag tag-{{ $reservation->status }}">{{ $reservation->status_label }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <p class="form-hint" style="margin-top:20px">{{ $stats['prestations'] }} prestation(s) active(s) sur le site.</p>
@endsection
