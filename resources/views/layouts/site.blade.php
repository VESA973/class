<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Class&rsquo;Affaire, entreprise de location de voiture avec chauffeur depuis 2021.')">
    <title>@yield('title', 'CLASS&rsquo;AFFAIRE - Location voiture avec chauffeur')</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="{{ asset('css/rentlux.css') }}">
</head>
<body>
    <header class="site-header" data-header>
        <a class="brand" href="{{ route('home') }}" aria-label="CLASS&rsquo;AFFAIRE">
            <span class="brand-mark">CA</span>
            <span>CLASS&rsquo;AFFAIRE</span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" data-menu-toggle>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" data-menu>
            <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('home')])>Accueil</a>
            <a href="{{ route('vehicles.page') }}" @class(['is-active' => request()->routeIs('vehicles.page')])>Vehicules</a>
            <a href="{{ route('prestations.page') }}" @class(['is-active' => request()->routeIs('prestations.page')])>Prestations</a>
            <a href="{{ route('contact.page') }}" @class(['is-active' => request()->routeIs('contact.page')])>Contact</a>
        </nav>

        <a class="header-cta" href="tel:+33180114483">+33 1 80 11 44 83</a>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <span>CLASS&rsquo;AFFAIRE</span>
        <span>Location de voitures de luxe avec ou sans chauffeur.</span>
    </footer>

    <script src="{{ asset('js/rentlux.js') }}" defer></script>
</body>
</html>
