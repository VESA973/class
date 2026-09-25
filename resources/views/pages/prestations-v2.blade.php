@extends('layouts.modern')

@section('title', 'Prestations avec chauffeur - CLASS’AFFAIRE')
@section('description', 'Mariages, transferts, soirées, évènements privés, voyages d’affaires : nos chauffeurs et leurs voitures à votre disposition.')

@section('content')
    <x-page-hero eyebrow="Prestations" title="Vos évènements, nos chauffeurs." text="Mariages, transferts, soirées et bien d’autres : nos chauffeurs et leurs voitures sont à votre disposition, avec la même exigence à chaque trajet." />

    <section class="pb-20" aria-label="Nos prestations">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($prestations->isEmpty())
                <div class="rounded-2xl border border-dashed border-border p-12 text-center text-muted-foreground">
                    <x-icon name="sparkles" class="mx-auto size-8" />
                    <p class="mt-3">Nos prestations sont en cours de mise à jour. Appelez-nous au {{ config('home.contact.phone') }}.</p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($prestations as $prestation)
                        <article @class([
                            'group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-card transition duration-300 hover:-translate-y-1 hover:border-white/20 hover:shadow-2xl hover:shadow-black/50',
                            'md:col-span-2 lg:col-span-2' => $loop->first && $prestations->count() > 2,
                        ]) data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 90 }}ms">
                            <div @class(['relative overflow-hidden bg-muted', 'aspect-[16/10]', 'lg:aspect-[21/9]' => $loop->first && $prestations->count() > 2])>
                                <img src="{{ $prestation->display_image }}" alt="" loading="lazy" decoding="async" onerror="this.hidden = true" class="size-full object-cover transition duration-700 group-hover:scale-105">
                                <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">Prestation</span>
                                    <h2 class="mt-1 text-2xl font-semibold text-white">{{ $prestation->name }}</h2>
                                </div>
                            </div>
                            @if ($prestation->description)
                                <p class="p-5 leading-relaxed text-muted-foreground sm:p-6">{{ $prestation->description }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="px-4 pb-24 sm:px-6 lg:px-8">
        <div class="relative isolate mx-auto max-w-7xl overflow-hidden rounded-3xl border border-white/10 bg-card px-6 py-14 text-center sm:px-12" data-reveal>
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.12),transparent_60%)]"></div>
            <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Un évènement à préparer ?</h2>
            <p class="mx-auto mt-4 max-w-xl text-muted-foreground">Choisissez votre véhicule avec chauffeur et vos horaires : nous confirmons rapidement chaque demande.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('booking.create') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-primary px-6 font-semibold text-primary-foreground transition hover:opacity-90">Réserver un véhicule <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('contact.page') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-md border border-white/15 px-6 font-semibold transition hover:bg-white/5">Nous contacter</a>
            </div>
        </div>
    </section>
@endsection
