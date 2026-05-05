<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Class’Affaire, entreprise de location de voiture avec chauffeur depuis 2021.">
    <title>CLASS’AFFAIRE - Location voiture avec chauffeur</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="{{ asset('css/rentlux.css') }}">
</head>
<body>
    <header class="site-header" data-header>
        <a class="brand" href="#accueil" aria-label="CLASS’AFFAIRE">
            <span class="brand-mark">CA</span>
            <span>CLASS’AFFAIRE</span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" data-menu-toggle>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" data-menu>
            <a href="#vehicules">Vehicules</a>
            <a href="#chauffeur">Avec chauffeur</a>
            <a href="#prestations">Prestations</a>
            <a href="#contact">Contact</a>
        </nav>

        <a class="header-cta" href="tel:+33180114483">+33 1 80 11 44 83</a>
    </header>

    <main id="accueil">
        <section class="hero">
            <picture>
                <source media="(max-width: 720px)" srcset="https://images.unsplash.com/photo-1600712242805-5f78671b24da?auto=format&fit=crop&w=900&q=80">
                <img src="https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=2200&q=82" alt="Voiture sportive de luxe sur route sombre">
            </picture>
            <div class="hero-overlay"></div>

            <div class="hero-content">
                <p class="eyebrow">Location premium avec chauffeur</p>
                <h1>CLASS’AFFAIRE</h1>
                <p class="hero-copy">Une selection de SUV, supercars et berlines avec reservation rapide, livraison sur demande et service chauffeur pour vos deplacements prives ou professionnels.</p>

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
                        Type de vehicule
                        <select id="categorySelect" name="category">
                            <option value="Tous">Tous les vehicules</option>
                            @foreach ($vehicles->pluck('category')->unique()->values() as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Modele
                        <select id="modelSelect" name="vehicle_id" required>
                            <option value="">Selectionner un modele</option>
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
                        Date de depart
                        <input id="startDateInput" type="date" name="start_date" required>
                    </label>
                    <label>
                        Nombre de jours
                        <input id="daysInput" type="number" name="days" min="1" value="3">
                    </label>
                    <label>
                        Lieu de depart
                        <input type="text" name="pickup_location" placeholder="Paris, aeroport, hotel..." required>
                    </label>
                    <label>
                        Nom complet
                        <input type="text" name="customer_name" placeholder="Votre nom" required>
                    </label>
                    <label>
                        Telephone
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
                <p>Depuis sa fondation en 2021, notre Entreprise de location de voiture avec chauffeur cherche à promouvoir un savoir faire, un service à la clientèle et des tarifs les meilleurs qui soient.</p>
                <p>Chez Class’Affaire, nous savons qu'offrir des locations d'une qualité irréprochable, même parmi les plus basiques, peut faire toute la différence dans le voyage de nos clients.</p>
                <p>Alors, que vous cherchiez de l'aide pour organiser votre prochaine aventure, votre prochain évènement ou vous souhaitez tout simplement des conseils pour louer le bon véhicule, contactez nous dès maintenant.</p>
            </div>
        </section>

        <section class="fleet-section" id="vehicules">
            <div class="section-heading">
                <p class="eyebrow">Nos vehicules</p>
                <h2>Catalogue disponible</h2>
            </div>

            <div class="filters" aria-label="Filtres vehicules">
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
                    <p class="empty-state">Aucun vehicule disponible pour le moment. Ajoutez vos vehicules dans l'administration.</p>
                @endforelse
            </div>
        </section>

        <section class="chauffeur-section" id="chauffeur">
            <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=1500&q=80" alt="Voiture de prestige devant un batiment moderne">
            <div>
                <p class="eyebrow">Avec chauffeur</p>
                <h2>Un service discret pour les transferts, evenements et voyages d'affaires.</h2>
                <p>Accueil aeroport, mise a disposition horaire, itineraires multi-adresses et vehicules de representation. Le chauffeur confirme les details avant chaque trajet.</p>
                <div class="stats">
                    <span><strong>24/7</strong> Disponibilite</span>
                    <span><strong>30 min</strong> Reponse moyenne</span>
                    <span><strong>Paris</strong> Cannes et Roissy</span>
                </div>
            </div>
        </section>

        <section class="prestations-section" id="prestations">
            <div class="section-heading">
                <p class="eyebrow">Prestations</p>
                <h2>Choisissez de faire appel à nous pour tous types d’évènements comme les mariages, les transferts, les soirées et bien d’autres.</h2>
            </div>
            <p class="prestations-lead">Nos chauffeurs et leurs voitures sont à votre disposition.</p>

            <div class="prestation-carousel" data-prestation-carousel>
                <div class="carousel-head">
                    <span data-carousel-count>01 / {{ str_pad((string) max($prestations->count(), 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="carousel-actions">
                        <button type="button" aria-label="Prestation precedente" data-carousel-prev>
                            <span aria-hidden="true">‹</span>
                        </button>
                        <button type="button" aria-label="Prestation suivante" data-carousel-next>
                            <span aria-hidden="true">›</span>
                        </button>
                    </div>
                </div>

                <div class="prestation-track" data-carousel-track>
                    @forelse ($prestations as $prestation)
                        <article class="prestation-card" data-carousel-card>
                            <img src="{{ $prestation->display_image }}" alt="{{ $prestation->name }}">
                            <div>
                                <span>Prestation</span>
                                <h3>{{ $prestation->name }}</h3>
                                @if ($prestation->description)
                                    <p>{{ $prestation->description }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="empty-state">Aucune prestation active pour le moment. Ajoutez vos prestations dans l'administration.</p>
                    @endforelse
                </div>

                <div class="carousel-dots" data-carousel-dots aria-label="Navigation des prestations"></div>
            </div>
        </section>

        <section class="contact-section" id="contact">
            <div>
                <p class="eyebrow">Contact</p>
                <h2>Votre demande de vehicule</h2>
                <p>Indiquez le modele, les dates et le lieu de depart. Un conseiller vous recontacte avec la disponibilite et les conditions.</p>
            </div>
            <div class="contact-panel">
                <a href="tel:+33180114483">+33 1 80 11 44 83</a>
                <a href="mailto:contact@classaffaire.fr">contact@classaffaire.fr</a>
                <span>174 Rue de la Belle Etoile, Roissy Charles de Gaulle</span>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span>CLASS’AFFAIRE</span>
        <span>Location de voitures de luxe avec ou sans chauffeur.</span>
    </footer>

    <script src="{{ asset('js/rentlux.js') }}" defer></script>
</body>
</html>
