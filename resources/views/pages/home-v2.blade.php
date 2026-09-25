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
@endpush

@section('content')
    {{-- FOND FIXE : la photo reste derriere toute la page (parallaxe + voile, voir script en bas) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden bg-background" aria-hidden="true">
        @if ($heroImageUrl)
            <img src="{{ $heroImageUrl }}" alt="" class="absolute inset-x-0 top-0 h-[115vh] w-full object-cover will-change-transform" fetchpriority="high" decoding="async" data-parallax>
        @else
            <img
                src="{{ $unsplash($defaultHero, 1600) }}"
                srcset="{{ $unsplash($defaultHero, 900) }} 900w, {{ $unsplash($defaultHero, 1600) }} 1600w, {{ $unsplash($defaultHero, 2400) }} 2400w"
                sizes="100vw" alt="" class="absolute inset-x-0 top-0 h-[115vh] w-full object-cover will-change-transform" fetchpriority="high" decoding="async" data-parallax>
        @endif
        {{-- Voile qui s'assombrit en descendant pour garder le texte lisible --}}
        <div class="absolute inset-0 bg-background opacity-0 will-change-[opacity]" data-parallax-veil></div>
    </div>

    {{-- HERO : la photo occupe tout l'ecran, le module de reservation est pose en bas --}}
    <section class="relative isolate flex min-h-svh flex-col justify-end pb-6 pt-24 sm:pb-10">
        <h1 class="sr-only">CLASS’AFFAIRE - {{ $content['hero']['title'] }}</h1>
        {{-- Degrades legers : haut (lisibilite du menu) et bas (module de reservation) ; le centre reste net --}}
        <div aria-hidden="true" class="absolute inset-x-0 top-0 -z-10 h-40 bg-gradient-to-b from-black/60 to-transparent"></div>
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 -z-10 h-[55%] bg-gradient-to-t from-black/75 via-black/30 to-transparent sm:h-[40%]"></div>

        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
            {{-- Module de recherche : formulaire HTML simple, remplace par le composant React --}}
            <div data-island="HeroSearch" data-props="{{ json_encode(['action' => route('booking.create')]) }}">
                <form action="{{ route('booking.create') }}" method="GET" class="grid gap-3 rounded-2xl border border-white/15 bg-black/45 p-4 text-white shadow-2xl backdrop-blur-xl sm:p-5 md:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <label class="grid gap-1.5 text-sm font-medium lg:col-span-5">Lieu de départ
                        <input name="pickup" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white placeholder:text-white/65" placeholder="Adresse, gare, aéroport…">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium lg:col-span-5">Destination
                        <input name="destination" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white placeholder:text-white/65" placeholder="Où allez-vous ?">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium md:col-span-2 lg:col-span-2">Passagers
                        <input type="number" name="passengers" min="1" max="9" value="1" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium lg:col-span-5">Départ
                        <input type="datetime-local" name="start" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white">
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium lg:col-span-5">Retour
                        <input type="datetime-local" name="end" class="h-11 rounded-md border border-white/25 bg-white/10 px-3 text-white">
                    </label>
                    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-white px-6 font-semibold text-[#101820] md:col-span-2 lg:col-span-2">
                        <x-icon name="search" class="size-4" /> Rechercher
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- AVANTAGES ------------------------------------------------------- --}}
    <section class="py-20 sm:py-28" aria-labelledby="avantages-titre">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl" data-reveal>
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Pourquoi nous choisir</p>
                <h2 id="avantages-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Le prestige, sans la complexité</h2>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($content['advantages'] as $advantage)
                    <div class="rounded-2xl border border-white/10 bg-card/70 p-6 backdrop-blur-md transition duration-300 hover:-translate-y-0.5 hover:border-white/20 hover:shadow-lg" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
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
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-300">Comment ça marche</p>
                <h2 id="etapes-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Votre véhicule en 3 étapes</h2>
            </div>
            <ol class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-8 md:before:absolute md:before:inset-x-[16.66%] md:before:top-7 md:before:h-px md:before:bg-border md:before:content-['']">
                @foreach ($content['steps'] as $step)
                    <li class="relative text-center" data-reveal style="--reveal-delay: {{ $loop->index * 120 }}ms">
                        <span class="relative mx-auto grid size-14 place-items-center rounded-full border border-white/15 bg-card/80 shadow-sm backdrop-blur-md">
                            <x-icon :name="$step['icon']" class="size-6" />
                            <span class="absolute -right-1 -top-1 grid size-6 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">{{ $loop->iteration }}</span>
                        </span>
                        <h3 class="mt-5 text-lg font-semibold [text-shadow:0_1px_8px_rgb(0_0_0/0.8)]">{{ $step['title'] }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-zinc-200 [text-shadow:0_1px_8px_rgb(0_0_0/0.8)]">{{ $step['text'] }}</p>
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
        <section class="py-20 sm:py-28" aria-labelledby="avis-titre">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">Avis clients</p>
                    <h2 id="avis-titre" class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Ils nous ont fait confiance</h2>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ($content['testimonials'] as $testimonial)
                        <figure class="flex flex-col rounded-2xl border border-white/10 bg-card/70 p-6 backdrop-blur-md" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
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

@endsection

@push('scripts')
    <script>
        // Fond fixe de la page d'accueil : la photo remonte lentement (parallaxe) et zoome
        // legerement, tandis qu'un voile sombre s'intensifie sur la hauteur du hero.
        // Mouvement reduit demande par l'utilisateur : pas de parallaxe, voile seulement.
        (function () {
            var image = document.querySelector('[data-parallax]');
            var veil = document.querySelector('[data-parallax-veil]');
            if (!image || !veil) {
                return;
            }

            var hero = document.querySelector('main > section');
            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var ticking = false;

            function update() {
                ticking = false;
                var scrolled = Math.max(window.scrollY, 0);
                var viewport = window.innerHeight;
                var heroProgress = Math.min(scrolled / (hero.offsetHeight * 0.9), 1);

                // Voile : 0 en haut de page, 72 % une fois le hero depasse (la voiture reste visible).
                veil.style.opacity = (heroProgress * 0.72).toFixed(3);

                if (!reduceMotion) {
                    // Remonte de 8 % de la distance defilee, au plus 15 % de l'ecran (photo haute de 115vh).
                    var shift = Math.min(scrolled * 0.08, viewport * 0.15);
                    image.style.transform = 'translate3d(0, ' + (-shift).toFixed(1) + 'px, 0) scale(' + (1 + heroProgress * 0.06).toFixed(4) + ')';
                }
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    ticking = true;
                    window.requestAnimationFrame(update);
                }
            }, { passive: true });
            window.addEventListener('resize', update, { passive: true });
            update();
        })();
    </script>
@endpush
