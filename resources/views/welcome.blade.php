<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Location de voitures de luxe avec ou sans chauffeur a Paris, Cannes et aeroport.">
    <title>Prestige Drive - Location voiture de luxe</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="{{ asset('css/rentlux.css') }}">
</head>
<body>
    <header class="site-header" data-header>
        <a class="brand" href="#accueil" aria-label="Prestige Drive">
            <span class="brand-mark">PD</span>
            <span>Prestige Drive</span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" data-menu-toggle>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" data-menu>
            <a href="#vehicules">Vehicules</a>
            <a href="#chauffeur">Avec chauffeur</a>
            <a href="#services">Services</a>
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
                <p class="eyebrow">Location premium a Paris, Cannes et aeroport</p>
                <h1>Voiture de luxe a Paris</h1>
                <p class="hero-copy">Une selection de SUV, supercars et berlines avec reservation rapide, livraison sur demande et service chauffeur pour vos deplacements prives ou professionnels.</p>

                <form class="booking-panel" id="bookingForm">
                    <label>
                        Type de vehicule
                        <select id="categorySelect" name="category">
                            <option value="Tous">Tous les vehicules</option>
                            <option value="SUV">SUV</option>
                            <option value="Supercar">Supercar</option>
                            <option value="Berline">Berline sportive</option>
                            <option value="Chauffeur">Avec chauffeur</option>
                        </select>
                    </label>
                    <label>
                        Modele
                        <select id="modelSelect" name="model">
                            <option value="">Selectionner un modele</option>
                        </select>
                    </label>
                    <label>
                        Nombre de jours
                        <input id="daysInput" type="number" name="days" min="1" value="3">
                    </label>
                    <div class="estimate">
                        <span>Prix estimatif</span>
                        <strong id="priceEstimate">900 EUR</strong>
                    </div>
                    <a class="primary-btn" id="reserveLink" href="https://wa.me/33180114483" target="_blank" rel="noreferrer">Reserver</a>
                </form>
            </div>
        </section>

        <section class="intro-section">
            <div class="section-heading">
                <p class="eyebrow">Experience sur mesure</p>
                <h2>Un parc haut de gamme pret pour chaque occasion.</h2>
            </div>
            <p>Choisissez votre vehicule, indiquez votre duree, puis recevez une confirmation claire. La page reprend l'esprit sombre, selectif et catalogue de Rentline, avec une identite differente et du contenu original.</p>
        </section>

        <section class="fleet-section" id="vehicules">
            <div class="section-heading">
                <p class="eyebrow">Nos vehicules</p>
                <h2>Catalogue disponible</h2>
            </div>

            <div class="filters" aria-label="Filtres vehicules">
                <button class="filter active" type="button" data-filter="Tous">Tous</button>
                <button class="filter" type="button" data-filter="SUV">SUV</button>
                <button class="filter" type="button" data-filter="Supercar">Supercar</button>
                <button class="filter" type="button" data-filter="Berline">Berline</button>
                <button class="filter" type="button" data-filter="Chauffeur">Avec chauffeur</button>
            </div>

            <div class="fleet-grid" id="fleetGrid">
                <article class="vehicle-card" data-category="SUV" data-price="1000" data-name="Lamborghini Urus">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1617814076668-9f44d08b4727?auto=format&fit=crop&w=900&q=80" alt="Lamborghini Urus noir">
                        <span>Disponible</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de 1000 EUR/jour</p>
                        <h3>Lamborghini Urus</h3>
                        <ul>
                            <li>641 ch</li>
                            <li>Essence</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>

                <article class="vehicle-card" data-category="Supercar" data-price="1750" data-name="Ferrari SF90 Stradale">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1592198084033-aade902d1aae?auto=format&fit=crop&w=900&q=80" alt="Ferrari rouge sportive">
                        <span>Disponible</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de 1750 EUR/jour</p>
                        <h3>Ferrari SF90 Stradale</h3>
                        <ul>
                            <li>769 ch</li>
                            <li>Hybride</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>

                <article class="vehicle-card" data-category="Berline" data-price="450" data-name="Porsche Panamera Turbo">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=900&q=80" alt="Porsche sombre dans un garage">
                        <span>Disponible</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de 450 EUR/jour</p>
                        <h3>Porsche Panamera Turbo</h3>
                        <ul>
                            <li>563 ch</li>
                            <li>Essence</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>

                <article class="vehicle-card" data-category="SUV" data-price="375" data-name="Mercedes GLC 63s AMG">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1617469767053-d3b523a0b982?auto=format&fit=crop&w=900&q=80" alt="Mercedes AMG stationnee">
                        <span>Disponible</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de 375 EUR/jour</p>
                        <h3>Mercedes GLC 63s AMG</h3>
                        <ul>
                            <li>503 ch</li>
                            <li>Essence</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>

                <article class="vehicle-card" data-category="Chauffeur" data-price="1200" data-name="Rolls Royce Ghost">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1631295868223-63265b40d9e4?auto=format&fit=crop&w=900&q=80" alt="Rolls Royce de luxe">
                        <span>Chauffeur</span>
                    </div>
                    <div class="vehicle-body">
                        <p>Service chauffeur</p>
                        <h3>Rolls Royce Ghost</h3>
                        <ul>
                            <li>563 ch</li>
                            <li>Essence</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>

                <article class="vehicle-card" data-category="Supercar" data-price="1500" data-name="Lamborghini Aventador S">
                    <div class="vehicle-media">
                        <img src="https://images.unsplash.com/photo-1621135802920-133df287f89c?auto=format&fit=crop&w=900&q=80" alt="Lamborghini sportive orange">
                        <span>Disponible</span>
                    </div>
                    <div class="vehicle-body">
                        <p>A partir de 1500 EUR/jour</p>
                        <h3>Lamborghini Aventador S</h3>
                        <ul>
                            <li>730 ch</li>
                            <li>Essence</li>
                            <li>Auto</li>
                        </ul>
                    </div>
                </article>
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

        <section class="services-section" id="services">
            <div class="section-heading">
                <p class="eyebrow">Services inclus</p>
                <h2>Tout ce qu'une location premium doit couvrir.</h2>
            </div>
            <div class="service-grid">
                <article>
                    <h3>Livraison flexible</h3>
                    <p>Hotel, domicile, gare ou aeroport selon votre programme.</p>
                </article>
                <article>
                    <h3>Selection verifiee</h3>
                    <p>Vehicules controles, propres et presentes avec conditions claires.</p>
                </article>
                <article>
                    <h3>Reservation rapide</h3>
                    <p>Estimation immediate puis confirmation par telephone ou WhatsApp.</p>
                </article>
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
                <a href="mailto:contact@prestigedrive.fr">contact@prestigedrive.fr</a>
                <span>174 Rue de la Belle Etoile, Roissy Charles de Gaulle</span>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span>Prestige Drive</span>
        <span>Location de voitures de luxe avec ou sans chauffeur.</span>
    </footer>

    <script src="{{ asset('js/rentlux.js') }}" defer></script>
</body>
</html>
