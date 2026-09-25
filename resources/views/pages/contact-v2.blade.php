@extends('layouts.modern')

@section('seo_page', 'contact')
@section('title', \App\Services\Seo::pageTitle('contact'))
@section('description', \App\Services\Seo::pageDescription('contact'))

@php($contact = config('home.contact'))


@section('content')
    <x-page-hero eyebrow="Contact" title="Parlons de votre prochain trajet." text="Transferts, évènements, voyages d’affaires ou demandes sur mesure : un conseiller vous répond avec les disponibilités et les conditions." />

    <section class="pb-24" aria-label="Coordonnées">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:px-8">
            <div class="grid gap-4">
                <a href="tel:{{ $contact['phone_href'] }}" class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-6 transition hover:-translate-y-0.5 hover:border-white/20" data-reveal>
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"><x-icon name="phone" class="size-5" /></span>
                    <span>
                        <span class="block text-sm text-muted-foreground">Téléphone · 24h/24, 7j/7</span>
                        <span class="text-xl font-semibold">{{ $contact['phone'] }}</span>
                    </span>
                    <x-icon name="arrow-right" class="ml-auto size-5 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-foreground" />
                </a>
                <a href="mailto:{{ $contact['email'] }}" class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-6 transition hover:-translate-y-0.5 hover:border-white/20" data-reveal style="--reveal-delay: 90ms">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"><x-icon name="mail" class="size-5" /></span>
                    <span>
                        <span class="block text-sm text-muted-foreground">Email · réponse en 30 min en moyenne</span>
                        <span class="text-xl font-semibold break-all">{{ $contact['email'] }}</span>
                    </span>
                    <x-icon name="arrow-right" class="ml-auto size-5 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-foreground" />
                </a>
                <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-6" data-reveal style="--reveal-delay: 180ms">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"><x-icon name="map-pin" class="size-5" /></span>
                    <span>
                        <span class="block text-sm text-muted-foreground">Adresse</span>
                        <span class="text-lg font-semibold">{{ $contact['address'] }}</span>
                    </span>
                </div>
                <a href="{{ route('booking.create') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-primary font-semibold text-primary-foreground transition hover:opacity-90" data-reveal style="--reveal-delay: 270ms">
                    <x-icon name="calendar-check" class="size-4" /> Réserver directement en ligne
                </a>
            </div>

            <div class="relative min-h-80 overflow-hidden rounded-2xl border border-white/10 bg-card" data-reveal>
                {{-- Carte OpenStreetMap (sans cle), assombrie pour le theme --}}
                <iframe
                    title="Plan d’accès : {{ $contact['address'] }}"
                    src="https://www.openstreetmap.org/export/embed.html?bbox=2.4990%2C48.9800%2C2.5210%2C48.9905&amp;layer=mapnik&amp;marker=48.985214%2C2.510013"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    class="absolute inset-0 size-full [filter:invert(0.92)_hue-rotate(180deg)_saturate(0.6)_brightness(0.95)]"
                ></iframe>
                <a href="https://www.openstreetmap.org/?mlat=48.985214&amp;mlon=2.510013#map=16/48.98521/2.51001" target="_blank" rel="noopener" class="absolute bottom-3 right-3 rounded-full bg-black/70 px-3 py-1.5 text-xs text-white backdrop-blur hover:bg-black/90">Agrandir le plan</a>
            </div>
        </div>
    </section>
@endsection
