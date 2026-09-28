@extends('admin.layout')

@section('title', 'Atouts de l’accueil')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>Atouts de l’accueil</h1>
            <p class="form-hint">Les cartes de la section « Pourquoi nous choisir » de la page d’accueil. Utilisez les flèches pour changer l’ordre.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('home') }}#avantages-titre" target="_blank" rel="noopener">Voir sur le site</a>
    </div>

    <form method="POST" action="{{ route('admin.content.advantages.update') }}" class="grid-stack" data-repeater>
        @csrf
        @method('PUT')

        @php($rows = old('advantages', $advantages))
        <div class="repeater-list" data-repeater-list>
            @foreach ($rows as $index => $advantage)
                @include('admin.content._advantage-row', ['index' => $index, 'advantage' => $advantage])
            @endforeach
        </div>

        <template data-repeater-template>
            @include('admin.content._advantage-row', ['index' => '__INDEX__', 'advantage' => ['icon' => 'sparkles', 'title' => '', 'text' => '']])
        </template>

        <p class="form-hint" data-repeater-empty @if (count($rows)) hidden @endif>Aucun atout : la section sera masquée sur la page d’accueil.</p>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-repeater-add data-max="{{ \App\Services\SiteContent::MAX_ADVANTAGES }}">Ajouter un atout</button>
            <button class="btn" type="submit">Enregistrer</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.content.advantages.reset') }}" onsubmit="return confirm('Revenir aux 6 atouts d’origine ?')" style="margin-top:16px">
        @csrf
        @method('DELETE')
        <button class="link-button" type="submit">Revenir aux atouts d’origine</button>
    </form>
@endsection

@include('admin.content._repeater-script')
