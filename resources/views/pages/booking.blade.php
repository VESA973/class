@extends('layouts.modern')

@section('title', 'Réserver un véhicule - CLASS’AFFAIRE')
@section('description', 'Réservez votre véhicule de prestige en ligne : choisissez vos dates, vérifiez les disponibilités et envoyez votre demande.')

@section('content')
    <x-page-hero eyebrow="Réservation" title="Réserver un véhicule" text="Choisissez votre véhicule et vos dates : les créneaux déjà réservés sont indiqués dans le calendrier." />

    <section class="pb-8">
        <div data-island="BookingForm" data-props="{{ json_encode($props) }}">
            <noscript>
                <p class="mx-auto max-w-7xl px-4">La réservation en ligne nécessite JavaScript. Vous pouvez aussi nous appeler au +33 1 80 11 44 83.</p>
            </noscript>
        </div>
    </section>
@endsection
