@extends('layouts.site')

@section('title', 'Réserver un véhicule - CLASS&rsquo;AFFAIRE')
@section('description', 'Réservez votre véhicule de prestige en ligne : choisissez vos dates, vérifiez les disponibilités et envoyez votre demande.')

@section('content')
    @vite(['resources/css/islands.css', 'resources/js/islands.tsx'])

    <section class="page-hero">
        <p class="eyebrow">Réservation</p>
        <h1>Réserver un véhicule</h1>
        <p>Choisissez votre véhicule et vos dates : les créneaux déjà réservés sont indiqués dans le calendrier.</p>
    </section>

    <section class="page-section booking-section">
        <div data-island="BookingForm" data-props="{{ json_encode($props) }}">
            <noscript>
                <p>La réservation en ligne nécessite JavaScript. Vous pouvez aussi nous appeler au +33 1 80 11 44 83.</p>
            </noscript>
        </div>
    </section>
@endsection
