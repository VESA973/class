@extends('layouts.site')

@section('title', 'Véhicules - CLASS&rsquo;AFFAIRE')

@section('content')
    <section class="page-hero">
        <p class="eyebrow">Véhicules</p>
        <h1>Catalogue disponible</h1>
        <p>Retrouvez notre sélection de véhicules disponibles avec ou sans chauffeur.</p>
    </section>

    <section class="fleet-section page-section">
        <div class="filters" aria-label="Filtres véhicules">
            <button class="filter active" type="button" data-filter="Tous">Tous</button>
            @foreach ($vehicles->pluck('category')->unique()->values() as $category)
                <button class="filter" type="button" data-filter="{{ $category }}">{{ $category }}</button>
            @endforeach
        </div>

        <div class="fleet-grid" id="fleetGrid">
            @forelse ($vehicles as $vehicle)
                <article class="vehicle-card" data-id="{{ $vehicle->id }}" data-category="{{ $vehicle->category }}" data-price="{{ $vehicle->daily_price }}" data-name="{{ $vehicle->name }}">
                    <div class="vehicle-media">
                        <img src="{{ $vehicle->display_image }}" alt="{{ $vehicle->name }}">
                        <span>{{ $vehicle->with_chauffeur ? 'Chauffeur' : 'Disponible' }}</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de {{ number_format($vehicle->daily_price, 0, ',', ' ') }} EUR/jour</p>
                        <h3>{{ $vehicle->name }}</h3>
                        <ul>
                            <li>{{ $vehicle->horsepower ? $vehicle->horsepower.' ch' : 'Puissance sur demande' }}</li>
                            <li>{{ $vehicle->fuel_type }}</li>
                            <li>{{ $vehicle->transmission }}</li>
                        </ul>
                    </div>
                </article>
            @empty
                <p class="empty-state">Aucun véhicule disponible pour le moment. Ajoutez vos véhicules dans l'administration.</p>
            @endforelse
        </div>
    </section>
@endsection
