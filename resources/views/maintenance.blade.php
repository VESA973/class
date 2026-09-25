@php
    $contact = config('home.contact');
    $logo = \Illuminate\Support\Facades\Schema::hasTable('site_settings') ? \App\Models\SiteSetting::current()->logo_url : null;
@endphp
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0a0a0a">
    <title>{{ $title }} - CLASS’AFFAIRE</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-svh place-items-center bg-background px-4 py-16 font-sans text-foreground antialiased">
    <div aria-hidden="true" class="fixed inset-0 -z-10 bg-[radial-gradient(ellipse_70%_50%_at_50%_0%,rgba(255,255,255,0.1),transparent_70%)]"></div>

    <main class="w-full max-w-xl text-center">
        <div class="mb-10 flex items-center justify-center gap-3 text-sm font-semibold uppercase tracking-[0.14em]">
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="h-10 w-auto">
            @else
                <span class="grid size-10 place-items-center rounded-lg border border-white/30 text-xs font-bold">CA</span>
            @endif
            CLASS’AFFAIRE
        </div>

        <span class="mx-auto grid size-14 place-items-center rounded-2xl border border-white/15 bg-card">
            <x-icon name="wrench" class="size-6" />
        </span>
        <h1 class="mt-6 text-4xl font-semibold tracking-tight text-balance sm:text-5xl">{{ $title }}</h1>
        <p class="mx-auto mt-5 max-w-md whitespace-pre-line text-lg leading-relaxed text-muted-foreground">{{ $message }}</p>

        @if ($returnAt && $returnAt->isFuture())
            <p class="mt-8 inline-flex items-center gap-2 rounded-full border border-white/15 bg-card px-4 py-2 text-sm">
                <x-icon name="clock" class="size-4" />
                Retour prévu le {{ $returnAt->locale('fr')->isoFormat('dddd D MMMM [à] HH[h]mm') }}
            </p>
        @endif

        <div class="mt-12 border-t border-white/10 pt-8 text-sm text-muted-foreground">
            <p>Une réservation urgente ? Nous restons joignables.</p>
            <div class="mt-4 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="tel:{{ $contact['phone_href'] }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 font-semibold text-primary-foreground"><x-icon name="phone" class="size-4" /> {{ $contact['phone'] }}</a>
                <a href="mailto:{{ $contact['email'] }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-md border border-white/15 px-5 font-medium text-foreground hover:bg-white/5"><x-icon name="mail" class="size-4" /> {{ $contact['email'] }}</a>
            </div>
        </div>
    </main>
</body>
</html>
