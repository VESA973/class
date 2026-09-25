@extends('admin.layout')

@section('title', 'Reservation')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demande n°{{ $reservation->id }} · <span class="tag">{{ $reservation->request_status_label }}</span></p>
            <h1>{{ $reservation->customer_name }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.reservations.index') }}">Retour</a>
    </div>

    <div class="detail-grid">
        <section class="detail-card">
            <h2>Client</h2>
            <p><strong>Telephone:</strong> {{ $reservation->customer_phone }}</p>
            <p><strong>Email:</strong> {{ $reservation->customer_email ?: '-' }}</p>
            <p><strong>Départ :</strong> {{ $reservation->pickup_location }}</p>
            <p><strong>Destination :</strong> {{ $reservation->destination ?: '-' }}</p>
            <p><strong>Passagers :</strong> {{ $reservation->passengers ?: '-' }}</p>
            <p><strong>Message:</strong> {{ $reservation->message ?: '-' }}</p>
        </section>

        <section class="detail-card">
            <h2>Vehicule</h2>
            <img class="preview" src="{{ $reservation->vehicle->display_image }}" alt="{{ $reservation->vehicle->name }}">
            <p><strong>Modele:</strong> {{ $reservation->vehicle->name }}</p>
            <p><strong>Prestation:</strong> {{ $reservation->prestation?->name ?: $reservation->service_type }}</p>
            <p><strong>Dates :</strong> {{ $reservation->start_at?->format('d/m/Y H:i') ?? $reservation->start_date->format('d/m/Y') }} au {{ $reservation->end_at?->format('d/m/Y H:i') ?? optional($reservation->end_date)->format('d/m/Y') }}</p>
            <p><strong>Statut :</strong> <span class="tag tag-{{ $reservation->status }}">{{ $reservation->status_label }}</span></p>
            <p><strong>Total estimatif:</strong> {{ number_format($reservation->estimated_total, 0, ',', ' ') }} EUR</p>
        </section>
    </div>

    @php($quotes = $reservation->quotes)
    <div class="settings-grid" style="margin-top:16px">
        <section class="form-card" aria-labelledby="quotes-title">
            <div class="section-head">
                <div>
                    <h2 id="quotes-title">Devis</h2>
                    <p class="form-hint">Pré-rempli avec le client, le véhicule, les dates et le tarif.</p>
                </div>
                <form method="POST" action="{{ route('admin.quotes.store', $reservation) }}">
                    @csrf
                    <button class="btn" type="submit">+ Créer un devis</button>
                </form>
            </div>
            @if ($quotes->isEmpty())
                <p class="empty-state" style="padding:12px 0">Aucun devis pour cette demande.</p>
            @else
                <ul class="panel-list" style="margin:0 -24px">
                    @foreach ($quotes as $quote)
                        <li>
                            <span><a href="{{ route('admin.quotes.edit', $quote) }}"><strong>{{ $quote->number }}</strong></a><small>{{ $quote->issued_at->format('d/m/Y') }} · {{ \App\Models\Quote::money($quote->total_ttc) }} TTC</small></span>
                            <span class="tag {{ ['draft' => 'tag-muted', 'sent' => 'tag-pending', 'accepted' => 'tag-success', 'refused' => 'tag-danger'][$quote->status] }}">{{ $quote->status_label }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="form-card" aria-labelledby="follow-title">
            <h2 id="follow-title">Suivi de la demande</h2>
            <form method="POST" action="{{ route('admin.reservations.request-status', $reservation) }}" class="form-card" style="padding:0;border:0;background:none">
                @csrf
                @method('PATCH')
                <label>Étape
                    <select name="request_status">
                        @foreach (\App\Models\Reservation::REQUEST_STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($reservation->request_status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="form-actions"><button class="btn btn-secondary" type="submit">Mettre à jour le suivi</button></div>
            </form>
            @php($events = $reservation->events()->with('user:id,name')->limit(20)->get())
            @if ($events->isNotEmpty())
                <hr class="divider">
                <h2>Historique</h2>
                @include('admin.reservations._timeline', ['events' => $events])
            @endif
        </section>
    </div>

    <form class="form-card compact" method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
        @csrf
        @method('PATCH')
        <label>
            Statut de la réservation (planning et disponibilités)
            <select name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($reservation->status === $status)>{{ \App\Models\Reservation::STATUS_LABELS[$status] ?? $status }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn" type="submit">Mettre a jour</button>
    </form>
@endsection
