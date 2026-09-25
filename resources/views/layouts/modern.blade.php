@php
    $contact = config('home.contact');
    $navigation = [
        ['home', 'Accueil', 'home'],
        ['vehicles.page', 'Véhicules', 'vehicles.*'],
        ['prestations.page', 'Prestations', 'prestations.*'],
        ['contact.page', 'Contact', 'contact.*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo-head')
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0a0a0a">
    @include('partials.favicon')
    {{-- Theme sombre uniquement. La classe "js" active les animations d'apparition. --}}
    <script>document.documentElement.classList.add('js');</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">
    <link rel="preconnect" href="https://images.unsplash.com">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/islands.tsx'])
</head>
<body class="min-h-svh bg-background font-sans text-foreground antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground">Aller au contenu</a>

    <header class="fixed inset-x-0 top-0 z-50 transition-colors duration-300 data-[scrolled=true]:border-b data-[scrolled=true]:border-border data-[scrolled=true]:bg-background/85 data-[scrolled=true]:backdrop-blur-lg" data-site-header>
        <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-wide text-white data-[scrolled=true]:text-foreground" aria-label="CLASS’AFFAIRE, accueil" data-header-text>
                @if ($siteSettings?->logo_url)
                    <img src="{{ $siteSettings->logo_url }}" alt="" class="h-auto max-h-12 w-auto" style="width: {{ min($siteSettings->logo_width ?? 46, 120) }}px">
                @else
                    <span class="grid size-10 place-items-center rounded-lg border border-current text-sm font-bold">CA</span>
                @endif
                <span class="text-sm uppercase sm:text-base">CLASS’AFFAIRE</span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                @foreach ($navigation as [$routeName, $label, $pattern])
                    <a href="{{ route($routeName) }}" class="rounded-md px-3 py-2 text-sm font-medium text-white/85 transition hover:bg-white/10 hover:text-white aria-[current=page]:text-white aria-[current=page]:underline aria-[current=page]:underline-offset-8 data-[scrolled=true]:text-muted-foreground data-[scrolled=true]:hover:bg-accent data-[scrolled=true]:hover:text-foreground data-[scrolled=true]:aria-[current=page]:text-foreground" data-header-text @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="tel:{{ $contact['phone_href'] }}" class="hidden items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-white/85 transition hover:text-white md:flex data-[scrolled=true]:text-muted-foreground data-[scrolled=true]:hover:text-foreground" data-header-text>
                    <x-icon name="phone" class="size-4" /> {{ $contact['phone'] }}
                </a>
                <a href="{{ route('booking.create') }}" class="hidden h-10 items-center gap-2 rounded-md bg-white px-4 text-sm font-semibold text-[#101820] shadow-sm transition hover:bg-white/90 sm:inline-flex">
                    Réserver <x-icon name="arrow-right" class="size-4" />
                </a>
                <button type="button" class="grid size-10 place-items-center rounded-md text-white hover:bg-white/10 lg:hidden data-[scrolled=true]:text-foreground data-[scrolled=true]:hover:bg-accent" aria-expanded="false" aria-controls="menu-mobile" data-menu-button data-header-text>
                    <span class="sr-only">Menu</span>
                    <x-icon name="menu" class="size-6" />
                </button>
            </div>
        </div>

        <div id="menu-mobile" class="hidden border-t border-border bg-background/95 backdrop-blur-lg lg:hidden" data-menu>
            <nav class="mx-auto grid max-w-7xl gap-1 px-4 py-4 sm:px-6" aria-label="Navigation mobile">
                @foreach ($navigation as [$routeName, $label, $pattern])
                    <a href="{{ route($routeName) }}" class="rounded-md px-3 py-3 font-medium hover:bg-accent aria-[current=page]:bg-accent" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <a href="{{ route('booking.create') }}" class="mt-2 inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary font-semibold text-primary-foreground">Réserver un véhicule</a>
                <a href="tel:{{ $contact['phone_href'] }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-md border border-border font-medium"><x-icon name="phone" class="size-4" /> {{ $contact['phone'] }}</a>
            </nav>
        </div>
    </header>

    @isset($maintenanceBypass)
        <div class="fixed inset-x-0 bottom-0 z-[70] flex flex-wrap items-center justify-center gap-x-3 gap-y-1 border-t border-amber-400/40 bg-amber-950/95 px-4 py-2 text-center text-sm text-amber-200 backdrop-blur" role="status">
            Mode maintenance activé : les visiteurs voient la page de maintenance.
            <a href="{{ route('admin.parameters.edit') }}" class="font-semibold underline underline-offset-4">Paramètres</a>
        </div>
    @endisset

    <main id="contenu">
        @yield('content')
    </main>

    <footer class="border-t border-border bg-card">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <p class="text-lg font-semibold uppercase tracking-wide">CLASS’AFFAIRE</p>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground">Location de voitures de prestige avec ou sans chauffeur depuis 2021. Transferts, évènements et voyages d’affaires à Paris, Cannes et Roissy.</p>
            </div>
            <nav aria-label="Liens du pied de page">
                <p class="text-sm font-semibold">Navigation</p>
                <ul class="mt-3 grid gap-2 text-sm text-muted-foreground">
                    <li><a class="hover:text-foreground" href="{{ route('booking.create') }}">Réserver</a></li>
                    <li><a class="hover:text-foreground" href="{{ route('vehicles.page') }}">Véhicules</a></li>
                    <li><a class="hover:text-foreground" href="{{ route('prestations.page') }}">Prestations</a></li>
                    <li><a class="hover:text-foreground" href="{{ route('contact.page') }}">Contact</a></li>
                </ul>
            </nav>
            <div>
                <p class="text-sm font-semibold">Contact</p>
                <ul class="mt-3 grid gap-2.5 text-sm text-muted-foreground">
                    <li><a class="inline-flex items-center gap-2 hover:text-foreground" href="tel:{{ $contact['phone_href'] }}"><x-icon name="phone" class="size-4" /> {{ $contact['phone'] }}</a></li>
                    <li><a class="inline-flex items-center gap-2 hover:text-foreground" href="mailto:{{ $contact['email'] }}"><x-icon name="mail" class="size-4" /> {{ $contact['email'] }}</a></li>
                    <li class="flex gap-2"><x-icon name="map-pin" class="mt-0.5 size-4" /> {{ $contact['address'] }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-border">
            <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-muted-foreground sm:px-6 lg:px-8">© {{ now()->year }} CLASS’AFFAIRE. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        (function () {
            var header = document.querySelector('[data-site-header]');
            var menu = document.querySelector('[data-menu]');
            var menuButton = document.querySelector('[data-menu-button]');
            var tinted = document.querySelectorAll('[data-header-text]');

            function onScroll() {
                var scrolled = window.scrollY > 24 || !menu.classList.contains('hidden');
                header.dataset.scrolled = scrolled;
                tinted.forEach(function (el) { el.dataset.scrolled = scrolled; });
            }

            menuButton.addEventListener('click', function () {
                var open = menu.classList.toggle('hidden') === false;
                menuButton.setAttribute('aria-expanded', open);
                onScroll();
            });

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            // Apparition douce des sections au defilement.
            var reveal = document.querySelectorAll('[data-reveal]');
            if (!('IntersectionObserver' in window)) {
                reveal.forEach(function (el) { el.classList.add('is-revealed'); });
                return;
            }
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '0px 0px -10% 0px' });
            reveal.forEach(function (el) { observer.observe(el); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
