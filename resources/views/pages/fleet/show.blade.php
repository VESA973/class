@extends('layouts.modern')

@section('title', \App\Services\Seo::vehicleDefaults($vehicle)['title'])
@section('description', \App\Services\Seo::vehicleDefaults($vehicle)['description'])

@php
    $has3d = (bool) $vehicle->model_url;
    $hasVideo = (bool) $vehicle->video_url;
    $specs = array_filter([
        ['gauge', 'Puissance', $vehicle->horsepower ? $vehicle->horsepower.' ch' : null],
        ['users', 'Places', $vehicle->seats ? $vehicle->seats.' places' : null],
        ['settings-2', 'Boîte', $vehicle->transmission],
        ['fuel', 'Carburant', $vehicle->fuel_type],
        ['user-round', 'Chauffeur', $vehicle->with_chauffeur ? 'Inclus' : 'En option'],
        ['sparkles', 'Catégorie', $vehicle->category],
    ], fn ($spec) => $spec[2] !== null);
    $bookUrl = route('booking.create', ['vehicle' => $vehicle->id]);
    // SEO : balises propres au vehicule + donnees structurees schema.org (Car, fil d'Ariane).
    $seoModel = $vehicle;
    $seoImage = $vehicle->display_image;
    $seoSchemas = app(\App\Services\Seo::class)->vehicleSchemas($vehicle);
@endphp

@if ($has3d)
    @push('head')
        @vite('resources/js/model-viewer.ts')
    @endpush
@endif

@section('content')
    {{-- SCENE 3D + RESUME --}}
    <section class="relative isolate overflow-hidden pb-16 pt-28 sm:pt-32">
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_70%_55%_at_40%_35%,rgba(255,255,255,0.14),transparent_70%)]"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <a href="{{ route('vehicles.page') }}" class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground">
                <x-icon name="arrow-left" class="size-4" /> Tous les véhicules
            </a>

            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
                <div>
                    @if ($has3d && $hasVideo)
                        <div class="mb-3 inline-flex rounded-full border border-white/15 p-1" role="tablist" aria-label="Mode d’affichage">
                            <button type="button" role="tab" aria-selected="true" aria-controls="scene-3d" class="inline-flex h-9 items-center gap-1.5 rounded-full px-4 text-sm font-medium aria-selected:bg-primary aria-selected:text-primary-foreground" data-scene-tab="scene-3d"><x-icon name="rotate-3d" class="size-4" /> 3D interactive</button>
                            <button type="button" role="tab" aria-selected="false" aria-controls="scene-video" class="inline-flex h-9 items-center gap-1.5 rounded-full px-4 text-sm font-medium aria-selected:bg-primary aria-selected:text-primary-foreground" data-scene-tab="scene-video"><x-icon name="play" class="size-4" /> Vidéo</button>
                        </div>
                    @endif

                    <div class="relative aspect-[4/3] overflow-hidden rounded-3xl border border-white/10 bg-[radial-gradient(ellipse_at_50%_70%,#27272a,#0a0a0a_75%)] sm:aspect-[16/10]">
                        @if ($has3d)
                            <div id="scene-3d" role="tabpanel" class="absolute inset-0" data-scene-panel>
                                <model-viewer
                                    src="{{ $vehicle->model_url }}"
                                    poster="{{ $vehicle->display_image }}"
                                    alt="Modèle 3D de la {{ $vehicle->name }}, à faire pivoter"
                                    camera-controls
                                    auto-rotate
                                    auto-rotate-delay="1500"
                                    rotation-per-second="18deg"
                                    interaction-prompt="auto"
                                    camera-orbit="-35deg 78deg auto"
                                    shadow-intensity="1.2"
                                    shadow-softness="0.9"
                                    exposure="1.1"
                                    environment-image="neutral"
                                    touch-action="pan-y"
                                    ar
                                    ar-modes="webxr scene-viewer quick-look"
                                    class="size-full"
                                    style="--poster-color: transparent; background: transparent;"
                                    data-model-viewer
                                >
                                    <div slot="progress-bar" class="absolute inset-x-8 bottom-6 h-1 overflow-hidden rounded-full bg-white/10" data-model-progress>
                                        <div class="h-full w-0 bg-white transition-[width] duration-200" data-model-progress-bar></div>
                                    </div>
                                    <button slot="ar-button" class="absolute bottom-4 right-4 inline-flex h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-black shadow-lg">
                                        <x-icon name="box" class="size-4" /> Voir chez moi
                                    </button>
                                </model-viewer>
                                <p class="pointer-events-none absolute bottom-4 left-4 inline-flex items-center gap-2 rounded-full bg-black/50 px-3 py-1.5 text-xs text-white/85 backdrop-blur">
                                    <x-icon name="rotate-3d" class="size-4" /> Faites glisser pour tourner · pincez pour zoomer
                                </p>
                            </div>
                        @endif

                        @if ($hasVideo)
                            <div id="scene-video" role="tabpanel" class="absolute inset-0" @if ($has3d) hidden @endif data-scene-panel>
                                <video class="size-full object-cover" src="{{ $vehicle->video_url }}" poster="{{ $vehicle->display_image }}" autoplay muted loop playsinline preload="metadata" aria-label="Vidéo 3D de la {{ $vehicle->name }}"></video>
                            </div>
                        @endif

                        @unless ($has3d || $hasVideo)
                            {{-- Pas encore de 3D : photo mise en scene --}}
                            <x-icon name="car-front" class="absolute left-1/2 top-1/2 size-12 -translate-x-1/2 -translate-y-1/2 text-muted-foreground/50" />
                            <img src="{{ $vehicle->display_image }}" alt="{{ $vehicle->name }}" class="relative size-full object-cover" fetchpriority="high" onerror="this.hidden = true">
                            <p class="absolute bottom-4 left-4 inline-flex items-center gap-2 rounded-full bg-black/60 px-3 py-1.5 text-xs text-white/85 backdrop-blur">
                                <x-icon name="image" class="size-4" /> Vue 3D bientôt disponible
                            </p>
                        @endunless
                    </div>
                </div>

                {{-- Resume + reservation --}}
                <aside class="rounded-3xl border border-white/10 bg-card/80 p-6 backdrop-blur-md lg:sticky lg:top-24">
                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ $vehicle->category }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $vehicle->name }}</h1>
                    <p class="mt-4 text-muted-foreground">
                        À partir de <span class="text-3xl font-semibold text-foreground">{{ number_format($vehicle->daily_price, 0, ',', ' ') }} €</span> / jour
                    </p>
                    <ul class="mt-6 grid grid-cols-2 gap-3 text-sm">
                        @foreach (array_slice($specs, 0, 4) as [$icon, $label, $value])
                            <li class="rounded-xl bg-secondary/70 p-3">
                                <x-icon :name="$icon" class="size-4 text-muted-foreground" />
                                <span class="mt-2 block text-xs text-muted-foreground">{{ $label }}</span>
                                <span class="font-medium">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ $bookUrl }}" class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-md bg-primary font-semibold text-primary-foreground transition hover:opacity-90">
                        <x-icon name="calendar-check" class="size-4" /> Réserver ce véhicule
                    </a>
                    <a href="tel:{{ config('home.contact.phone_href') }}" class="mt-3 inline-flex h-12 w-full items-center justify-center gap-2 rounded-md border border-white/15 font-medium transition hover:bg-white/5">
                        <x-icon name="phone" class="size-4" /> {{ config('home.contact.phone') }}
                    </a>
                    <p class="mt-4 text-center text-xs text-muted-foreground">Disponibilités en temps réel · aucun paiement en ligne</p>
                </aside>
            </div>
        </div>
    </section>

    {{-- CARACTERISTIQUES --}}
    <section class="border-t border-white/10 py-16 sm:py-20" aria-labelledby="specs-titre">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="specs-titre" class="text-2xl font-semibold tracking-tight sm:text-3xl" data-reveal>Caractéristiques</h2>
            <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($specs as [$icon, $label, $value])
                    <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-5" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 80 }}ms">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"><x-icon :name="$icon" class="size-5" /></span>
                        <div>
                            <dt class="text-sm text-muted-foreground">{{ $label }}</dt>
                            <dd class="text-lg font-semibold">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>

            @if ($vehicle->description)
                <div class="mt-12 max-w-3xl" data-reveal>
                    <h2 class="text-2xl font-semibold tracking-tight">À propos de ce véhicule</h2>
                    <p class="mt-4 whitespace-pre-line leading-relaxed text-muted-foreground">{{ $vehicle->description }}</p>
                </div>
            @endif
        </div>
    </section>

    {{-- AUTRES VEHICULES --}}
    @if ($others->isNotEmpty())
        <section class="border-t border-white/10 py-16 pb-28 sm:py-20" aria-labelledby="autres-titre">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4" data-reveal>
                    <h2 id="autres-titre" class="text-2xl font-semibold tracking-tight sm:text-3xl">Vous aimerez aussi</h2>
                    <a href="{{ route('vehicles.page') }}" class="inline-flex items-center gap-2 text-sm font-semibold hover:underline">Toute la flotte <x-icon name="arrow-right" class="size-4" /></a>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($others as $other)
                        <x-vehicle-card :vehicle="$other" :delay="$loop->index * 90" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Barre de reservation fixe sur mobile (+ espace pour ne pas masquer le pied de page) --}}
    <div aria-hidden="true" class="h-20 lg:hidden"></div>
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-background/90 p-3 backdrop-blur-lg lg:hidden">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground"><span class="text-lg font-semibold text-foreground">{{ number_format($vehicle->daily_price, 0, ',', ' ') }} €</span> / jour</p>
            <a href="{{ $bookUrl }}" class="inline-flex h-11 items-center gap-2 rounded-md bg-primary px-5 font-semibold text-primary-foreground">Réserver <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            // Onglets 3D / video
            var tabs = document.querySelectorAll('[data-scene-tab]');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (other) {
                        var active = other === tab;
                        other.setAttribute('aria-selected', active);
                        document.getElementById(other.dataset.sceneTab).hidden = !active;
                    });
                    var video = document.querySelector('#scene-video video');
                    if (video) { tab.dataset.sceneTab === 'scene-video' ? video.play() : video.pause(); }
                });
            });

            // Barre de chargement du modele 3D
            var viewer = document.querySelector('[data-model-viewer]');
            if (viewer) {
                var bar = viewer.querySelector('[data-model-progress-bar]');
                var track = viewer.querySelector('[data-model-progress]');
                viewer.addEventListener('progress', function (event) {
                    var progress = event.detail.totalProgress;
                    bar.style.width = (progress * 100) + '%';
                    track.hidden = progress >= 1;
                });
                // Mouvement reduit : pas de rotation automatique.
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    viewer.removeAttribute('auto-rotate');
                }
            }
        })();
    </script>
@endpush
