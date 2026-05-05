@extends('admin.layout')

@section('title', 'Reservation')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Reservation #{{ $reservation->id }}</p>
            <h1>{{ $reservation->customer_name }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.reservations.index') }}">Retour</a>
    </div>

    <div class="detail-grid">
        <section class="detail-card">
            <h2>Client</h2>
            <p><strong>Telephone:</strong> {{ $reservation->customer_phone }}</p>
            <p><strong>Email:</strong> {{ $reservation->customer_email ?: '-' }}</p>
            <p><strong>Lieu:</strong> {{ $reservation->pickup_location }}</p>
            <p><strong>Message:</strong> {{ $reservation->message ?: '-' }}</p>
        </section>

        <section class="detail-card">
            <h2>Vehicule</h2>
            <img class="preview" src="{{ $reservation->vehicle->display_image }}" alt="{{ $reservation->vehicle->name }}">
            <p><strong>Modele:</strong> {{ $reservation->vehicle->name }}</p>
            <p><strong>Type:</strong> {{ $reservation->service_type }}</p>
            <p><strong>Dates:</strong> {{ $reservation->start_date->format('d/m/Y') }} au {{ optional($reservation->end_date)->format('d/m/Y') }}</p>
            <p><strong>Total estimatif:</strong> {{ number_format($reservation->estimated_total, 0, ',', ' ') }} EUR</p>
        </section>
    </div>

    <form class="form-card compact" method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
        @csrf
        @method('PATCH')
        <label>
            Statut
            <select name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($reservation->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn" type="submit">Mettre a jour</button>
    </form>
@endsection
