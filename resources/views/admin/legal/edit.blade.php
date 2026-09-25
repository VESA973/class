@extends('admin.layout')

@section('title', $page->title)

@section('content')
    @vite(['resources/css/islands.css', 'resources/js/islands.tsx'])

    <div class="page-head">
        <div>
            <p class="eyebrow">Page légale</p>
            <h1>{{ $page->title }}</h1>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ $page->url }}" target="_blank" rel="noopener">Voir la page</a>
            <a class="btn btn-secondary" href="{{ route('admin.seo.legal.edit', $page) }}">SEO et adresse</a>
        </div>
    </div>

    @include('admin.legal._tabs')
    @include('admin.legal._missing')

    <div class="editor-grid legal-grid">
        <form class="form-card" method="POST" action="{{ route('admin.legal.update', $page) }}">
            @csrf
            @method('PUT')
            <label>Titre<input name="title" value="{{ old('title', $page->title) }}" required maxlength="160"></label>
            <div>
                <p class="field-note">Contenu</p>
                <div data-island="LegalEditor" data-props="{{ json_encode(['target' => '#legal-content', 'variables' => $variables]) }}"></div>
                <textarea id="legal-content" name="content" rows="24" class="mono" required>{{ old('content', $page->content) }}</textarea>
                <p class="form-hint">Les informations entre accolades (ex. {raison_sociale}) sont remplacées automatiquement par celles de l’onglet « Informations de l’entreprise ».</p>
            </div>
            <label>Note sur cette modification (facultatif, visible dans l’historique)<input name="note" maxlength="255" placeholder="Ex. mise à jour des durées de conservation"></label>
            <div class="checkboxes"><label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Page publiée (visible sur le site et dans le pied de page)</label></div>
            <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
        </form>

        <section class="form-card" aria-labelledby="versions-title">
            <h2 id="versions-title">Historique des versions</h2>
            <ol class="timeline">
                @foreach ($versions as $version)
                    <li>
                        <span class="timeline-dot" aria-hidden="true"></span>
                        <div>
                            <p>{{ $version->note ?: 'Modification' }}</p>
                            <small>{{ $version->created_at->timezone(config('app.local_timezone'))->format('d/m/Y à H:i') }} · {{ $version->user?->name ?? 'Système' }}
                                · <a href="{{ route('admin.legal.version', [$page, $version]) }}" style="text-decoration:underline">Voir</a>
                                @unless ($loop->first)
                                    · <form method="POST" action="{{ route('admin.legal.restore', [$page, $version]) }}" style="display:inline" onsubmit="return confirm('Restaurer cette version ? Le contenu actuel sera remplacé (il reste dans l’historique).')">@csrf<button type="submit" class="link-button" style="font-size:12px">Restaurer</button></form>
                                @endunless
                            </small>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
@endsection
