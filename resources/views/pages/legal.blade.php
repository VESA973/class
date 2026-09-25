@extends('layouts.modern')

@php
    $seoModel = $page;
    // Sommaire genere a partir des titres de section.
    preg_match_all('/<h2>(.*?)<\/h2>/s', $html, $headings);
    $toc = [];
    $html = preg_replace_callback('/<h2>(.*?)<\/h2>/s', function ($match) use (&$toc) {
        $id = \Illuminate\Support\Str::slug(strip_tags($match[1])) ?: 'section-'.count($toc);
        $toc[] = ['id' => $id, 'title' => strip_tags($match[1])];
        return '<h2 id="'.$id.'">'.$match[1].'</h2>';
    }, $html);
@endphp

@section('title', $page->title.' - CLASS’AFFAIRE')
@section('description', $page->title.' du site CLASS’AFFAIRE, location de voitures de prestige avec ou sans chauffeur.')

@section('content')
    <x-page-hero eyebrow="Informations légales" :title="$page->title">
        <p class="mt-5 text-sm text-muted-foreground">Dernière mise à jour : {{ $page->updated_at->timezone(config('app.local_timezone'))->locale('fr')->isoFormat('D MMMM YYYY') }}</p>
    </x-page-hero>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 pb-24 sm:px-6 lg:grid-cols-[240px_minmax(0,1fr)] lg:px-8">
        <aside class="lg:sticky lg:top-28 lg:self-start">
            @if (count($toc) > 1)
                <nav aria-label="Sommaire" class="rounded-2xl border border-white/10 bg-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">Sommaire</p>
                    <ol class="mt-3 grid gap-2 text-sm">
                        @foreach ($toc as $item)
                            <li><a href="#{{ $item['id'] }}" class="text-muted-foreground transition hover:text-foreground">{{ $item['title'] }}</a></li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            @if ($others->isNotEmpty())
                <nav aria-label="Autres pages légales" class="mt-4 grid gap-2 text-sm">
                    @foreach ($others as $other)
                        <a href="/{{ $other->slug }}" class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">{{ $other->title }}</a>
                    @endforeach
                </nav>
            @endif
        </aside>

        <article class="legal-prose max-w-3xl">{!! $html !!}</article>
    </div>
@endsection
