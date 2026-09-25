@props(['eyebrow', 'title', 'text' => null])
{{-- En-tete des pages interieures (design "modern", theme sombre) --}}
<section class="relative isolate overflow-hidden pb-14 pt-36 sm:pb-20 sm:pt-44">
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(255,255,255,0.12),transparent_70%)]"></div>
    <div aria-hidden="true" class="absolute inset-x-0 bottom-0 -z-10 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" data-reveal>
        <p class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ $eyebrow }}</p>
        <h1 class="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">{{ $title }}</h1>
        @if ($text)
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted-foreground">{{ $text }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
