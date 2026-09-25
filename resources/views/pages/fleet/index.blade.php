@extends('layouts.modern')

@section('seo_page', 'vehicles')
@section('title', \App\Services\Seo::pageTitle('vehicles'))
@section('description', \App\Services\Seo::pageDescription('vehicles'))


@section('content')
    <x-page-hero eyebrow="Notre flotte" title="Des véhicules d’exception, à découvrir en 3D." text="SUV, supercars et berlines de prestige, avec ou sans chauffeur. Ouvrez une fiche pour faire tourner le véhicule et vérifier ses disponibilités." />

    <section class="pb-24" aria-label="Catalogue">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($vehicles->isEmpty())
                <div class="rounded-2xl border border-dashed border-border p-12 text-center text-muted-foreground">
                    <x-icon name="car-front" class="mx-auto size-8" />
                    <p class="mt-3">Notre flotte est en cours de mise à jour. Appelez-nous au {{ config('home.contact.phone') }}.</p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrer par catégorie">
                        <button type="button" class="h-10 rounded-full border border-white/15 px-4 text-sm font-medium transition hover:border-white/30 aria-pressed:border-transparent aria-pressed:bg-primary aria-pressed:text-primary-foreground" aria-pressed="true" data-filter="all">Tous</button>
                        @foreach ($categories as $category)
                            <button type="button" class="h-10 rounded-full border border-white/15 px-4 text-sm font-medium transition hover:border-white/30 aria-pressed:border-transparent aria-pressed:bg-primary aria-pressed:text-primary-foreground" aria-pressed="false" data-filter="{{ $category }}">{{ $category }}</button>
                        @endforeach
                    </div>
                    <p class="text-sm text-muted-foreground" aria-live="polite" data-filter-count>{{ $vehicles->count() }} véhicule(s)</p>
                </div>

                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vehicles as $vehicle)
                        <x-vehicle-card :vehicle="$vehicle" :delay="($loop->index % 3) * 90" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // Filtre par categorie, sans rechargement.
        (function () {
            var buttons = document.querySelectorAll('[data-filter]');
            var cards = document.querySelectorAll('[data-category]');
            var count = document.querySelector('[data-filter-count]');

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var filter = button.dataset.filter;
                    var visible = 0;

                    buttons.forEach(function (other) { other.setAttribute('aria-pressed', other === button); });
                    cards.forEach(function (card) {
                        var show = filter === 'all' || card.dataset.category === filter;
                        card.hidden = !show;
                        if (show) { visible++; card.classList.add('is-revealed'); }
                    });
                    count.textContent = visible + ' véhicule(s)';
                });
            });
        })();
    </script>
@endpush
