@extends('layouts.site')

@section('title', 'Prestations - CLASS&rsquo;AFFAIRE')

@section('content')
    <section class="page-hero">
        <p class="eyebrow">Prestations</p>
        <h1>Vos evenements, nos chauffeurs.</h1>
        <p>Choisissez de faire appel a nous pour tous types d'evenements comme les mariages, les transferts, les soirees et bien d'autres.</p>
    </section>

    <section class="prestations-section page-section">
        <p class="prestations-lead">Nos chauffeurs et leurs voitures sont a votre disposition.</p>

        <div class="prestation-carousel" data-prestation-carousel>
            <div class="carousel-head">
                <span data-carousel-count>01 / {{ str_pad((string) max($prestations->count(), 1), 2, '0', STR_PAD_LEFT) }}</span>
                <div class="carousel-actions">
                    <button type="button" aria-label="Prestation precedente" data-carousel-prev>
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>
                    <button type="button" aria-label="Prestation suivante" data-carousel-next>
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </div>
            </div>

            <div class="prestation-track" data-carousel-track>
                @forelse ($prestations as $prestation)
                    <article class="prestation-card" data-carousel-card>
                        <img src="{{ $prestation->display_image }}" alt="{{ $prestation->name }}">
                        <div>
                            <span>Prestation</span>
                            <h3>{{ $prestation->name }}</h3>
                            @if ($prestation->description)
                                <p>{{ $prestation->description }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="empty-state">Aucune prestation active pour le moment. Ajoutez vos prestations dans l'administration.</p>
                @endforelse
            </div>

            <div class="carousel-dots" data-carousel-dots aria-label="Navigation des prestations"></div>
        </div>
    </section>
@endsection
