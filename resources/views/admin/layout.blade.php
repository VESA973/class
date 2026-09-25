@php
    $siteSettings = \Illuminate\Support\Facades\Schema::hasTable('site_settings') ? \App\Models\SiteSetting::current() : null;
    $pendingCount = \App\Models\Reservation::where('status', 'pending')->count();

    // Menu par sections. 'soon' = module de la refonte pas encore livre (affiche, non cliquable).
    $navigation = [
        ['title' => null, 'items' => [
            ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'layout-dashboard'],
        ]],
        ['title' => 'Véhicules', 'items' => [
            ['route' => 'admin.vehicles.index', 'pattern' => 'admin.vehicles.*', 'label' => 'Véhicules', 'icon' => 'car-front'],
            ['route' => 'admin.prestations.index', 'pattern' => 'admin.prestations.*', 'label' => 'Prestations', 'icon' => 'sparkles'],
        ]],
        ['title' => 'Demandes & Devis', 'items' => [
            ['route' => 'admin.reservations.index', 'pattern' => 'admin.reservations.*', 'label' => 'Demandes', 'icon' => 'clipboard-list', 'badge' => $pendingCount],
            ['route' => 'admin.planning.index', 'pattern' => 'admin.planning.*', 'label' => 'Planning', 'icon' => 'calendar-days'],
            ['route' => 'admin.quotes.index', 'pattern' => 'admin.quotes.*', 'label' => 'Devis', 'icon' => 'file-text'],
        ]],
        ['title' => 'Communication', 'items' => [
            ['route' => 'admin.emails.settings', 'pattern' => 'admin.emails.*', 'label' => 'Emails', 'icon' => 'mail'],
        ]],
        ['title' => 'Site', 'items' => [
            ['route' => 'admin.hero.edit', 'pattern' => 'admin.hero.*', 'label' => "Photo d'accueil", 'icon' => 'image'],
            ['route' => 'admin.seo.index', 'pattern' => 'admin.seo.*', 'label' => 'SEO', 'icon' => 'search'],
            ['route' => 'admin.legal.index', 'pattern' => 'admin.legal.*', 'label' => 'Pages légales', 'icon' => 'scale'],
            ['label' => 'Cookies', 'icon' => 'cookie', 'soon' => true],
        ]],
        ['title' => 'Paramètres', 'items' => [
            ['route' => 'admin.settings.edit', 'pattern' => 'admin.settings.*', 'label' => 'Logo du site', 'icon' => 'settings'],
            ['route' => 'admin.parameters.edit', 'pattern' => 'admin.parameters.*', 'label' => 'Favicon & maintenance', 'icon' => 'wrench'],
            ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Utilisateurs', 'icon' => 'users'],
        ]],
    ];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark">
    <title>@yield('title', 'Administration') - CLASS’AFFAIRE</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @stack('head')
</head>
<body>
    <a href="#admin-content" class="sr-only">Aller au contenu</a>

    {{-- Barre du haut (tablette / mobile) --}}
    <header class="admin-topbar">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span class="admin-brand-mark">CA</span> CLASS’AFFAIRE
        </a>
        <button type="button" class="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false" data-admin-menu>
            <span class="sr-only">Ouvrir le menu</span>
            <x-icon name="menu" class="size-5" style="width:20px;height:20px" />
        </button>
    </header>
    <div class="admin-overlay" data-admin-overlay></div>

    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu d’administration">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            @if ($siteSettings?->logo_url)
                <img src="{{ $siteSettings->logo_url }}" alt="">
            @else
                <span class="admin-brand-mark">CA</span>
            @endif
            CLASS’AFFAIRE
        </a>

        <nav aria-label="Navigation de l’administration">
            @foreach ($navigation as $section)
                <div class="nav-section">
                    @if ($section['title'])
                        <p class="nav-section-title">{{ $section['title'] }}</p>
                    @endif
                    @foreach ($section['items'] as $item)
                        @if (! empty($item['soon']))
                            <span class="nav-link is-soon" aria-disabled="true" title="Disponible dans une prochaine étape de la refonte">
                                <x-icon :name="$item['icon']" /> {{ $item['label'] }} <span class="nav-soon">Bientôt</span>
                            </span>
                        @else
                            <a class="nav-link" href="{{ route($item['route']) }}" @if (request()->routeIs($item['pattern'])) aria-current="page" @endif>
                                <x-icon :name="$item['icon']" /> {{ $item['label'] }}
                                @if (! empty($item['badge']))
                                    <span class="nav-soon" style="opacity:1;border-color:rgba(251,191,36,.4);color:#fbbf24" title="{{ $item['badge'] }} réservation(s) en attente">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="admin-sidebar-footer">
            <a class="nav-link" href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external-link" /> Voir le site</a>
            @if ($user)
                <div class="admin-user">
                    <span class="admin-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <span>{{ $user->name }}<small>{{ $user->email }}</small></span>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="nav-link"><x-icon name="log-out" /> Déconnexion</button>
            </form>
        </div>
    </aside>

    <main class="admin-main" id="admin-content">
        @if (app(\App\Services\Settings::class)->get('maintenance.enabled') && ! request()->routeIs('admin.parameters.*'))
            <div class="maintenance-banner" role="status">
                <span>Mode maintenance activé : les visiteurs voient la page de maintenance.</span>
                <a href="{{ route('admin.parameters.edit') }}">Gérer</a>
            </div>
        @endif

        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        // Tiroir du menu sur tablette / mobile.
        (function () {
            var button = document.querySelector('[data-admin-menu]');
            var overlay = document.querySelector('[data-admin-overlay]');
            function toggle(open) {
                document.body.classList.toggle('admin-menu-open', open);
                button.setAttribute('aria-expanded', open);
            }
            button.addEventListener('click', function () { toggle(!document.body.classList.contains('admin-menu-open')); });
            overlay.addEventListener('click', function () { toggle(false); });
            document.addEventListener('keydown', function (event) { if (event.key === 'Escape') toggle(false); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
