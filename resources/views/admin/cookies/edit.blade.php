@extends('admin.layout')

@section('title', 'Cookies')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>Cookies</h1>
        </div>
        <form method="POST" action="{{ route('admin.cookies.renew') }}" onsubmit="return confirm('Redemander le consentement à tous les visiteurs ?')">
            @csrf
            <button class="btn btn-secondary" type="submit">Redemander le consentement à tous (version {{ $config['version'] }})</button>
        </form>
    </div>

    @include('admin.cookies._tabs')

    <form method="POST" action="{{ route('admin.cookies.update') }}" class="settings-grid">
        @csrf
        @method('PUT')

        <section class="form-card">
            <h2>Bandeau</h2>
            <label class="toggle-row">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $config['enabled']))>
                <span><strong>Afficher le bandeau cookies</strong><small>Obligatoire dès qu’un cookie non essentiel (mesure d’audience, publicité) est utilisé.</small></span>
            </label>
            <label>Titre<input name="texts[title]" value="{{ old('texts.title', $config['texts']['title']) }}" required maxlength="120"></label>
            <label>Message<textarea name="texts[message]" rows="4" required maxlength="1000">{{ old('texts.message', $config['texts']['message']) }}</textarea></label>
            <div class="form-grid">
                <label>Bouton « accepter »<input name="texts[accept]" value="{{ old('texts.accept', $config['texts']['accept']) }}" required maxlength="40"></label>
                <label>Bouton « refuser »<input name="texts[reject]" value="{{ old('texts.reject', $config['texts']['reject']) }}" required maxlength="40"></label>
                <label>Lien « personnaliser »<input name="texts[customize]" value="{{ old('texts.customize', $config['texts']['customize']) }}" required maxlength="40"></label>
                <label>Bouton « enregistrer »<input name="texts[save]" value="{{ old('texts.save', $config['texts']['save']) }}" required maxlength="40"></label>
            </div>
            <p class="form-hint">Conformément aux recommandations de la CNIL, « Tout accepter » et « Tout refuser » ont toujours le même style.</p>

            <h2>Couleurs</h2>
            <div class="form-grid color-grid">
                @foreach (['background' => 'Fond', 'text' => 'Texte', 'button' => 'Boutons', 'button_text' => 'Texte des boutons'] as $key => $label)
                    <label>{{ $label }}<input type="color" name="colors[{{ $key }}]" value="{{ old('colors.'.$key, $config['colors'][$key]) }}"></label>
                @endforeach
            </div>
        </section>

        <section class="form-card">
            <h2>Catégories et scripts</h2>
            <p class="form-hint">Collez ici les codes de suivi (Google Analytics, Meta Pixel…). Ils ne sont <strong>jamais chargés avant le consentement</strong> de la catégorie correspondante.</p>
            @foreach (\App\Services\CookieSettings::CATEGORIES as $key)
                @php($category = $config['categories'][$key])
                <fieldset class="category-box">
                    <legend>{{ $category['label'] }}</legend>
                    <label>Nom affiché<input name="categories[{{ $key }}][label]" value="{{ old("categories.$key.label", $category['label']) }}" required maxlength="80"></label>
                    <label>Description<textarea name="categories[{{ $key }}][description]" rows="2" required maxlength="500">{{ old("categories.$key.description", $category['description']) }}</textarea></label>
                    <label>Scripts (chargés seulement après accord)<textarea name="categories[{{ $key }}][scripts]" rows="5" class="mono" maxlength="20000" placeholder="<script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXX&quot;></script>">{{ old("categories.$key.scripts", $category['scripts']) }}</textarea></label>
                    <label>Cookies à supprimer en cas de refus (séparés par des virgules)<input name="categories[{{ $key }}][cookies]" value="{{ old("categories.$key.cookies", $category['cookies']) }}" maxlength="500"></label>
                </fieldset>
            @endforeach
            <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
        </section>
    </form>
@endsection
