@extends('layouts.modern')

@section('seo_page', 'contact')
@section('title', \App\Services\Seo::pageTitle('contact'))
@section('description', \App\Services\Seo::pageDescription('contact'))

@php($contact = config('home.contact'))
@php($whatsappUrl = app(\App\Services\ContactSettings::class)->whatsappUrl())


@section('content')
    <x-page-hero eyebrow="Contact" title="Parlons de votre prochain trajet." text="Transferts, évènements, voyages d’affaires ou demandes sur mesure : un conseiller vous répond avec les disponibilités et les conditions." />

    <section class="pb-24" aria-label="Coordonnées">
        {{-- Plan d'acces retire (geolocalisation). L'adresse ne s'affiche que si elle est activee dans l'admin. --}}
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
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
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-6 transition hover:-translate-y-0.5 hover:border-white/20" data-reveal style="--reveal-delay: 135ms">
                        <span class="grid size-12 shrink-0 place-items-center rounded-xl text-white" style="background:#25d366"><x-icon name="message-circle" class="size-5" /></span>
                        <span>
                            <span class="block text-sm text-muted-foreground">WhatsApp · message ou appel</span>
                            <span class="text-xl font-semibold">Écrire sur WhatsApp</span>
                        </span>
                        <x-icon name="arrow-right" class="ml-auto size-5 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-foreground" />
                    </a>
                @endif
                @if ($contact['show_address'] ?? false)
                <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-card p-6" data-reveal style="--reveal-delay: 180ms">
                    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary text-primary-foreground"><x-icon name="map-pin" class="size-5" /></span>
                    <span>
                        <span class="block text-sm text-muted-foreground">Adresse</span>
                        <span class="text-lg font-semibold">{{ $contact['address'] }}</span>
                    </span>
                </div>
                @endif
                <a href="{{ route('booking.create') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-primary font-semibold text-primary-foreground transition hover:opacity-90" data-reveal style="--reveal-delay: 270ms">
                    <x-icon name="calendar-check" class="size-4" /> Réserver directement en ligne
                </a>
            </div>

        </div>
    </section>
@endsection

