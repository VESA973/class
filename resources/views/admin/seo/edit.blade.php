@extends('admin.layout')

@section('title', 'SEO - '.$name)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">SEO</p>
            <h1>{{ $name }}</h1>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ $url }}" target="_blank" rel="noopener">Voir la page</a>
            <a class="btn btn-secondary" href="{{ route('admin.seo.index') }}">Retour</a>
        </div>
    </div>

    @include('admin.seo._tabs')

    <div class="editor-grid">
        <form class="form-card" method="POST" action="{{ $action }}" enctype="multipart/form-data" data-seo-form>
            @csrf
            @method('PUT')

            @if ($slug)
                <label>Adresse de la page
                    <span class="slug-input"><span>{{ $slug['prefix'] }}</span><input name="slug" value="{{ old('slug', $slug['value']) }}" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" data-seo-slug></span>
                    <span class="form-hint">Si vous la modifiez, l’ancienne adresse redirige automatiquement vers la nouvelle (301).</span>
                </label>
            @endif

            <label>Titre (balise title)
                <input name="title" value="{{ old('title', $meta?->title) }}" maxlength="120" placeholder="{{ $defaultTitle }}" data-seo-title data-default="{{ $defaultTitle }}">
                <span class="form-hint"><span data-count="title">0</span> / 60 caractères conseillés. Vide = titre automatique.</span>
            </label>
            <label>Description (balise meta description)
                <textarea name="description" rows="3" maxlength="300" placeholder="{{ $defaultDescription }}" data-seo-description data-default="{{ $defaultDescription }}">{{ old('description', $meta?->description) }}</textarea>
                <span class="form-hint"><span data-count="description">0</span> / 155 caractères conseillés. Vide = description automatique.</span>
            </label>

            <label>Image de partage (Open Graph, 1200×630 conseillé)
                <input type="file" name="og_image" accept="image/*">
            </label>
            @if ($meta?->og_image_url)
                <div class="checkboxes">
                    <img src="{{ $meta->og_image_url }}" alt="" class="thumb" style="width:160px;height:84px">
                    <label><input type="checkbox" name="remove_og_image" value="1"> Retirer cette image</label>
                </div>
            @endif

            <label>Adresse canonique (facultatif)
                <input type="url" name="canonical_url" value="{{ old('canonical_url', $meta?->canonical_url) }}" placeholder="{{ $url }}">
                <span class="form-hint">À renseigner seulement si le même contenu existe à une autre adresse.</span>
            </label>

            <label class="toggle-row">
                <input type="checkbox" name="noindex" value="1" @checked(old('noindex', $meta?->noindex))>
                <span><strong>Ne pas indexer cette page (noindex)</strong><small>La page reste accessible mais n’apparaît plus dans Google ni dans le sitemap.</small></span>
            </label>

            <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
        </form>

        <section class="form-card" aria-labelledby="preview-title">
            <h2 id="preview-title">Aperçu dans Google</h2>
            <div class="google-preview">
                <div class="gp-site"><span class="gp-favicon">CA</span><span><strong>CLASS’AFFAIRE</strong><small data-preview-url>{{ $url }}</small></span></div>
                <div class="gp-title" data-preview-title></div>
                <div class="gp-description" data-preview-description></div>
            </div>
            <p class="form-hint">Aperçu indicatif : Google peut réécrire le titre ou la description.</p>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.querySelector('[data-seo-form]');
            var title = form.querySelector('[data-seo-title]');
            var description = form.querySelector('[data-seo-description]');
            var slug = form.querySelector('[data-seo-slug]');
            var out = {
                title: document.querySelector('[data-preview-title]'),
                description: document.querySelector('[data-preview-description]'),
                url: document.querySelector('[data-preview-url]')
            };
            var baseUrl = out.url.textContent;
            function cut(text, max) { return text.length > max ? text.slice(0, max - 1).trim() + '…' : text; }
            function update() {
                var t = title.value.trim() || title.dataset.default;
                var d = description.value.trim() || description.dataset.default;
                out.title.textContent = cut(t, 60);
                out.description.textContent = cut(d, 158);
                form.querySelector('[data-count="title"]').textContent = t.length;
                form.querySelector('[data-count="description"]').textContent = d.length;
                var url = slug ? baseUrl.replace(/[^\/]+$/, slug.value) : baseUrl;
                out.url.textContent = url.replace(/^https?:\/\//, '').replace(/\//g, ' › ');
            }
            [title, description, slug].forEach(function (el) { el && el.addEventListener('input', update); });
            update();
        })();
    </script>
@endpush
