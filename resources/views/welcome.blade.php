@extends('layouts.site')

@section('content')
    <section class="hero">
        <picture>
            <source media="(max-width: 720px)" srcset="https://images.unsplash.com/photo-1600712242805-5f78671b24da?auto=format&fit=crop&w=900&q=80">
            <img src="https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=2200&q=82" alt="Voiture sportive de luxe sur route sombre">
        </picture>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <p class="eyebrow">Location premium avec chauffeur</p>
            <h1>CLASS&rsquo;AFFAIRE</h1>
            <p class="hero-copy">Une sélection de SUV, supercars et berlines avec réservation rapide, livraison sur demande et service chauffeur pour vos déplacements privés ou professionnels.</p>

            @if (session('reservation_success'))
                <div class="success-alert">{{ session('reservation_success') }}</div>
            @endif

            @if ($errors->any())
                <div class="success-alert error-alert">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form class="booking-panel" id="bookingForm" method="POST" action="{{ route('reservations.store') }}">
                @csrf
                <label>
                    Type de véhicule
                    <select id="categorySelect" name="category">
                        <option value="Tous">Tous les véhicules</option>
                        @foreach ($vehicles->pluck('category')->unique()->values() as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Modèle
                    <select id="modelSelect" name="vehicle_id" required>
                        <option value="">Sélectionner un modèle</option>
                    </select>
                </label>
                <label>
                    Prestation
                    <select name="prestation_id">
                        <option value="">Choisir une prestation</option>
                        @foreach ($prestations as $prestation)
                            <option value="{{ $prestation->id }}">{{ $prestation->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Date de départ
                    <input id="startDateInput" type="date" name="start_date" required>
                </label>
                <label>
                    Nombre de jours
                    <input id="daysInput" type="number" name="days" min="1" value="3">
                </label>
                <label>
                    Lieu de départ
                    <input type="text" name="pickup_location" placeholder="Paris, aéroport, hôtel..." required>
                </label>
                <label>
                    Nom complet
                    <input type="text" name="customer_name" placeholder="Votre nom" required>
                </label>
                <label>
                    Téléphone
                    <input type="tel" name="customer_phone" placeholder="+33..." required>
                </label>
                <label>
                    Email
                    <input type="email" name="customer_email" placeholder="email@exemple.fr">
                </label>
                <div class="estimate">
                    <span>Prix estimatif</span>
                    <strong id="priceEstimate">900 EUR</strong>
                </div>
                <button class="primary-btn" type="submit">Envoyer</button>
            </form>
        </div>
    </section>

    <section class="intro-section">
        <div class="section-heading">
            <p class="eyebrow">Quelques mots</p>
            <h2>En quelques mots</h2>
        </div>
        <div class="intro-copy">
            <p>Depuis sa fondation en 2021, notre entreprise de location de voiture avec chauffeur cherche à promouvoir un savoir-faire, un service à la clientèle et des tarifs les meilleurs qui soient.</p>
            <p>Chez Class&rsquo;Affaire, nous savons qu'offrir des locations d'une qualité irréprochable, même parmi les plus basiques, peut faire toute la différence dans le voyage de nos clients.</p>
            <p>Alors, que vous cherchiez de l'aide pour organiser votre prochaine aventure, votre prochain évènement ou vous souhaitez tout simplement des conseils pour louer le bon véhicule, contactez-nous dès maintenant.</p>
        </div>
    </section>

    <section class="chauffeur-section">
        <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1500&q=80" alt="Voiture de prestige devant un batiment moderne">
        <div>
            <p class="eyebrow">Avec chauffeur</p>
            <h2>Un service discret pour les transferts, évènements et voyages d'affaires.</h2>
            <p>Accueil aéroport, mise à disposition horaire, itinéraires multi-adresses et véhicules de représentation. Le chauffeur confirme les détails avant chaque trajet.</p>
            <div class="stats">
                <span><strong>24/7</strong> Disponibilité</span>
                <span><strong>30 min</strong> Réponse moyenne</span>
                <span><strong>Paris</strong> Cannes et Roissy</span>
            </div>
        </div>
    </section>
@endsection
