{{-- Balises SEO et partage (Admin > SEO). Titre/description de la page = valeurs par defaut. --}}
@php
    $seoPageKey = trim($__env->yieldContent('seo_page'));
    $seoMeta = app(\App\Services\Seo::class)->resolve(
        $seoPageKey !== '' ? 'page:'.$seoPageKey : null,
        $seoModel ?? null,
        html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5),
        html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES | ENT_HTML5),
        $seoImage ?? null,
    );
@endphp
<title>{{ $seoMeta['title'] }}</title>
<meta name="description" content="{{ $seoMeta['description'] }}">
<meta name="robots" content="{{ $seoMeta['robots'] }}">
<link rel="canonical" href="{{ $seoMeta['canonical'] }}">
<meta property="og:type" content="{{ $seoMeta['type'] }}">
<meta property="og:site_name" content="{{ $seoMeta['site_name'] }}">
<meta property="og:title" content="{{ $seoMeta['title'] }}">
<meta property="og:description" content="{{ $seoMeta['description'] }}">
<meta property="og:url" content="{{ $seoMeta['canonical'] }}">
<meta property="og:locale" content="fr_FR">
@if ($seoMeta['image'])
    <meta property="og:image" content="{{ $seoMeta['image'] }}">
    <meta name="twitter:image" content="{{ $seoMeta['image'] }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoMeta['title'] }}">
<meta name="twitter:description" content="{{ $seoMeta['description'] }}">
<script type="application/ld+json">{!! \App\Services\Seo::jsonLd(app(\App\Services\Seo::class)->businessSchema()) !!}</script>
@foreach ($seoSchemas ?? [] as $schema)
    <script type="application/ld+json">{!! \App\Services\Seo::jsonLd($schema) !!}</script>
@endforeach
