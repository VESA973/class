@extends('layouts.modern')

@section('seo_page', 'booking')
@section('title', \App\Services\Seo::pageTitle('booking'))
@section('description', \App\Services\Seo::pageDescription('booking'))


@section('content')
    <x-page-hero eyebrow="Réservation" title="Réserver un véhicule" text="Choisissez votre véhicule et vos dates : les créneaux déjà réservés sont indiqués dans le calendrier." />

    <section class="pb-8">
        <div data-island="BookingForm" data-props="{{ json_encode($props) }}">
            <noscript>
                <p class="mx-auto max-w-7xl px-4">La réservation en ligne nécessite JavaScript. Vous pouvez aussi nous appeler au {{ config('home.contact.phone') }}.</p>
            </noscript>
        </div>
    </section>
@endsection
