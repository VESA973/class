@extends('admin.layout')

@section('title', $template->name)

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Modèle d’email</p>
            <h1>{{ $template->name }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.emails.templates') }}">Retour aux modèles</a>
    </div>

    @include('admin.emails._tabs')

    <div class="editor-grid">
        <form class="form-card" method="POST" action="{{ route('admin.emails.templates.update', $template) }}" data-template-form data-preview-url="{{ route('admin.emails.templates.preview', $template) }}">
            @csrf
            @method('PUT')
            <p class="form-hint">{{ $template->description }}</p>

            <label>Objet
                <input name="subject" value="{{ old('subject', $template->subject) }}" maxlength="255" required data-template-subject>
            </label>
            <label>Contenu
                <textarea name="body" rows="18" maxlength="10000" required data-template-body class="mono">{{ old('body', $template->body) }}</textarea>
            </label>
            <details class="help">
                <summary>Mise en forme</summary>
                <p><code># Titre</code> · <code>## Sous-titre</code> · <code>**gras**</code> · <code>- élément de liste</code> · <code>[Texte du bouton](https://…)</code> ou <code>[Texte]({lien_admin})</code>. Laissez une ligne vide entre deux paragraphes.</p>
            </details>

            <div class="checkboxes">
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active))> Modèle actif (décochez pour ne plus envoyer cet email)</label>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">Enregistrer</button>
            </div>
        </form>

        <div class="grid-stack">
            <section class="form-card" aria-labelledby="vars-title">
                <h2 id="vars-title">Variables disponibles</h2>
                <p class="form-hint">Cliquez pour insérer à l’endroit du curseur.</p>
                <div class="variable-list">
                    @foreach ($template->availableVariables() as $variable => $label)
                        <button type="button" class="variable-chip" data-variable="{{ '{'.$variable.'}' }}" title="{{ $label }}">
                            <code>{{ '{'.$variable.'}' }}</code><span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="form-card" aria-labelledby="preview-title">
                <h2 id="preview-title">Aperçu <small class="form-hint">(valeurs d’exemple)</small></h2>
                <p class="form-hint">Objet : <strong data-preview-subject>…</strong></p>
                <iframe title="Aperçu de l’email" class="email-preview" data-preview-frame sandbox></iframe>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.querySelector('[data-template-form]');
            var subject = form.querySelector('[data-template-subject]');
            var body = form.querySelector('[data-template-body]');
            var frame = document.querySelector('[data-preview-frame]');
            var subjectOut = document.querySelector('[data-preview-subject]');
            var lastField = body;
            var timer;

            [subject, body].forEach(function (field) {
                field.addEventListener('focus', function () { lastField = field; });
                field.addEventListener('input', schedule);
            });

            document.querySelectorAll('[data-variable]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var value = chip.dataset.variable;
                    var start = lastField.selectionStart, end = lastField.selectionEnd;
                    lastField.setRangeText(value, start, end, 'end');
                    lastField.focus();
                    schedule();
                });
            });

            function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 300); }

            function refresh() {
                var data = new FormData();
                data.append('subject', subject.value);
                data.append('body', body.value);
                fetch(form.dataset.previewUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
                    body: data
                }).then(function (response) { return response.json(); }).then(function (json) {
                    subjectOut.textContent = json.subject;
                    frame.srcdoc = json.html;
                }).catch(function () { subjectOut.textContent = 'Aperçu indisponible'; });
            }

            refresh();
        })();
    </script>
@endpush
