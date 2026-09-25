{{-- Icones du site (Parametres > Favicon & maintenance). Inclus dans le <head> de toutes les pages. --}}
@php
    $faviconSettings = app(\App\Services\Settings::class);
    $faviconVersion = $faviconSettings->get('favicon.version');
@endphp
@if ($faviconVersion)
    @php($faviconBase = \Illuminate\Support\Facades\Storage::disk('public')->url('favicon'))
    <link rel="icon" href="{{ $faviconBase }}/favicon.ico?v={{ $faviconVersion }}" sizes="48x48">
    @if ($faviconSettings->get('favicon.has_svg'))
        <link rel="icon" type="image/svg+xml" href="{{ $faviconBase }}/icon.svg?v={{ $faviconVersion }}">
    @endif
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $faviconBase }}/favicon-32x32.png?v={{ $faviconVersion }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $faviconBase }}/favicon-16x16.png?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $faviconBase }}/apple-touch-icon.png?v={{ $faviconVersion }}">
@else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
@endif
<link rel="manifest" href="{{ route('webmanifest') }}">
