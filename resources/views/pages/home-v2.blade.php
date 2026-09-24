@extends('layouts.modern')

@php
    $defaultHero = 'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&q=80';
    $unsplash = fn (string $url, int $width) => $url.(str_contains($url, '?') ? '&' : '?').'w='.$width;
@endphp

@push('head')
    @if ($heroImageUrl)
        <link rel="preload" as="image" href="{{ $heroImageUrl }}" fetchpriority="high">
    @else
        <link rel="preload" as="image" imagesrcset="{{ $unsplash($defaultHero, 900) }} 900w, {{ $unsplash($defaultHero, 1600) }} 1600w, {{ $unsplash($defaultHero, 2400) }} 2400w" imagesizes="100vw" fetchpriority="high">
    @endif
    @if (count($content['faq']))
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ], $content['faq']),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
@endpush

@section('content')
    {{-- HERO : la photo occupe tout l'ecran, le module de reservation est pose en bas --}}
    <section class="relative isolate flex min-h-svh flex-col justify-end overflow-hidden pb-6 pt-24 sm:pb-10">
        <h1 class="sr-only">CLASS’AFFAIRE - {{ $content['hero']['title'] }}</h1>

        @if ($heroImageUrl)
            <img src="{{ $heroImageUrl }}" alt="" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high" decoding="async">
        @else
            <img
                src="{{ $unsplash($defaultHero, 1600) }}"
                srcset="{{ $unsplash($defaultHero, 900) }} 900w, {{ $unsplash($defaultHero, 1600) }} 1600w, {{ $unsplash($defaultHero, 2400) }} 2400w"
                sizes="100vw" alt="" class="absolute inset-0 -z-20 size-full object-cover" fetchpriority="high" decoding="async">
        @endif
        {{-- Degrades legers : haut (lisibilite du menu) et bas (module de reservation) ; le centre reste net --}}
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-40 bg-gradient-to-b from-black/60 to-transparent"></div>
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 -z-10 h-[55%] bg-gradient-to-t from-black/75 via-black/30 to-transparent sm:h-[40%]"></div>

        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            {{-- Module de recherche : formulaire HTML simple, remplace par le composant React --}}
            <div data-island="HeroSearch" data-props="{{ json_encode(['action' => route('booking.create')]) }}">
                <form action="{{ route('booking.create') }}" method="GET" class="grid gap-3 rounded-2xl border border-white/15 bg-black/45 p-4 text-white shadow-2xl backdrop-blur-xl sm:p-5 md:grid-cols-2 xl:grid-cols-[1.2fr_1fr_1fr_auto] xl:items-end">
                    <label class="grid gap-1.5 text-sm font-medium">Lieu de prise en charge
                        <input name="pickup" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white placeholder:text-white/65" placeholder="Paris, aéroport, hôtel…">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Départ
                        <input type="datetime-local" name="start" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Retour
                        <input type="datetime-local" name="end" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white">
                    </label>
                    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-white px-6 font-semibold text-[#101820]">
                        <x-icon name="search" class="size-4" /> Rechercher
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- VEHICULES POPULAIRES -------------------------------------------- --}}
    <section class="py-20 sm:py-28" aria-labelledby="vehicules-titre">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Notre flotte</p>
                    <h2 id="vehicules-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Véhicules populaires</h2>
                </div>
                <a href="{{ route('vehicles.page') }}" class="inline-flex items-center gap-2 text-sm font-semibold hover:underline">
                    Voir tout le catalogue <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>

            @if ($vehicles->isEmpty())
                <div class="mt-10 rounded-2xl border border-dashed border-border p-10 text-center text-muted-foreground">
                    <x-icon name="car-front" class="mx-auto size-8" />
                    <p class="mt-3">Notre flotte est en cours de mise à jour. Appelez-nous au {{ $content['contact']['phone'] }} pour connaître les disponibilités.</p>
                </div>
            @else
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vehicles as $vehicle)
                        <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl focus-within:ring-2 focus-within:ring-ring" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
                            <div class="relative aspect-[16/10] overflow-hidden bg-muted">
                                <x-icon name="car-front" class="absolute left-1/2 top-1/2 size-10 -translate-x-1/2 -translate-y-1/2 text-muted-foreground/60" />
                                {{-- Image introuvable : on la masque, l'icone ci-dessus reste visible --}}
                                <img src="{{ $vehicle->display_image }}" alt="{{ $vehicle->name }}" loading="lazy" decoding="async" width="800" height="500" onerror="this.hidden = true" class="relative size-full object-cover transition duration-500 group-hover:scale-105">
                                <span class="absolute left-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{{ $vehicle->category }}</span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="text-lg font-semibold">{{ $vehicle->name }}</h3>
                                <ul class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground" aria-label="Caractéristiques">
                                    <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="fuel" class="size-3.5" /> {{ $vehicle->fuel_type }}</li>
                                    <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="settings-2" class="size-3.5" /> {{ $vehicle->transmission }}</li>
                                    @if ($vehicle->horsepower)
                                        <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="gauge" class="size-3.5" /> {{ $vehicle->horsepower }} ch</li>
                                    @endif
                                    @if ($vehicle->with_chauffeur)
                                        <li class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2 py-1"><x-icon name="user-round" class="size-3.5" /> Avec chauffeur</li>
                                    @endif
                                </ul>
                                <div class="mt-auto flex items-end justify-between gap-4 pt-6">
                                    <p class="text-sm text-muted-foreground">
                                        À partir de<br>
                                        <span class="text-2xl font-semibold text-foreground">{{ number_format($vehicle->daily_price, 0, ',', ' ') }} €</span> / jour
                                    </p>
                                    <a href="{{ route('booking.create', ['vehicle' => $vehicle->id]) }}" class="inline-flex h-10 items-center gap-1.5 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition hover:opacity-90 after:absolute after:inset-0">
                                        Réserver <span class="sr-only">{{ $vehicle->name }}</span> <x-icon name="arrow-right" class="size-4" />
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- AVANTAGES ------------------------------------------------------- --}}
    <section class="border-y border-border bg-secondary/40 py-20 sm:py-28" aria-labelledby="avantages-titre">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl" data-reveal>
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Pourquoi nous choisir</p>
                <h2 id="avantages-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Le prestige, sans la complexité</h2>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($content['advantages'] as $advantage)
                    <div class="rounded-2xl border border-border bg-card p-6 transition duration-300 hover:-translate-y-0.5 hover:shadow-lg" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
                        <span class="grid size-11 place-items-center rounded-xl bg-primary text-primary-foreground">
                            <x-icon :name="$advantage['icon']" class="size-5" />
                        </span>
                        <h3 class="mt-5 font-semibold">{{ $advantage['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ $advantage['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- COMMENT CA MARCHE ----------------------------------------------- --}}
    <section class="py-20 sm:py-28" aria-labelledby="etapes-titre">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center" data-reveal>
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Comment ça marche</p>
                <h2 id="etapes-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Votre véhicule en 3 étapes</h2>
            </div>
            <ol class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8 md:before:absolute md:before:inset-x-[16.66%] md:before:top-7 md:before:h-px md:before:bg-border md:before:content-['']">
                @foreach ($content['steps'] as $step)
                    <li class="relative text-center" data-reveal style="--reveal-delay: {{ $loop->index * 120 }}ms">
                        <span class="relative mx-auto grid size-14 place-items-center rounded-full border border-border bg-background shadow-sm">
                            <x-icon :name="$step['icon']" class="size-6" />
                            <span class="absolute -right-1 -top-1 grid size-6 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{{ $loop->iteration }}</span>
                        </span>
                        <h3 class="mt-5 text-lg font-semibold">{{ $step['title'] }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-muted-foreground">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
            <div class="mt-12 text-center" data-reveal>
                <a href="{{ route('booking.create') }}" class="inline-flex h-12 items-center gap-2 rounded-md bg-primary px-6 font-semibold text-primary-foreground transition hover:opacity-90">
                    Commencer ma réservation <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- AVIS CLIENTS (uniquement avec de vrais avis dans config/home.php) - --}}
    @if (count($content['testimonials']))
        <section class="border-y border-border bg-secondary/40 py-20 sm:py-28" aria-labelledby="avis-titre">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Avis clients</p>
                    <h2 id="avis-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Ils nous ont fait confiance</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($content['testimonials'] as $testimonial)
                        <figure class="flex flex-col rounded-2xl border border-border bg-card p-6" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
                            <div class="flex gap-0.5" aria-label="{{ $testimonial['rating'] }} sur 5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <x-icon name="star" @class(['size-4', 'fill-current text-amber-400' => $i <= $testimonial['rating'], 'text-muted-foreground/40' => $i > $testimonial['rating']]) />
                                @endfor
                            </div>
                            <blockquote class="mt-4 flex-1 leading-relaxed">“{{ $testimonial['text'] }}”</blockquote>
                            <figcaption class="mt-5 text-sm">
                                <span class="font-semibold">{{ $testimonial['name'] }}</span>
                                @if (! empty($testimonial['context']))
                                    <span class="text-muted-foreground"> · {{ $testimonial['context'] }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ ------------------------------------------------------------- --}}
    <section class="py-20 sm:py-28" aria-labelledby="faq-titre">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:px-8">
            <div data-reveal>
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">FAQ</p>
                <h2 id="faq-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Questions fréquentes</h2>
                <p class="mt-4 text-muted-foreground">Une autre question ? Appelez-nous au <a class="font-semibold text-foreground hover:underline" href="tel:{{ $content['contact']['phone_href'] }}">{{ $content['contact']['phone'] }}</a>.</p>
            </div>
            <div data-island="Faq" data-props="{{ json_encode(['items' => $content['faq']]) }}" data-reveal>
                {{-- Version sans JavaScript (et lisible par les moteurs de recherche) --}}
                <div class="divide-y divide-border rounded-2xl border border-border">
                    @foreach ($content['faq'] as $item)
                        <details class="group p-5">
                            <summary class="cursor-pointer font-medium">{{ $item['question'] }}</summary>
                            <p class="mt-3 text-muted-foreground">{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- APPEL A L'ACTION ------------------------------------------------ --}}
    <section class="px-4 pb-20 sm:px-6 sm:pb-28 lg:px-8">
        <div class="relative isolate mx-auto max-w-7xl overflow-hidden rounded-3xl bg-[#101820] px-6 py-14 text-center text-white sm:px-12" data-reveal>
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,rgba(187,188,189,0.25),transparent_60%)]"></div>
            <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Prêt à prendre la route ?</h2>
            <p class="mx-auto mt-4 max-w-xl text-white/75">Vérifiez les disponibilités en temps réel et envoyez votre demande en moins de deux minutes.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('booking.create') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-white px-6 font-semibold text-[#101820] transition hover:bg-white/90">
                    Réserver un véhicule <x-icon name="arrow-right" class="size-4" />
                </a>
                <a href="tel:{{ $content['contact']['phone_href'] }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md border border-white/30 px-6 font-semibold transition hover:bg-white/10">
                    <x-icon name="phone" class="size-4" /> {{ $content['contact']['phone'] }}
                </a>
            </div>
        </div>
    </section>
@endsection
