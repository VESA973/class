@extends('admin.layout')

@section('title', 'Favicon & maintenance')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Paramètres</p>
            <h1>Favicon & maintenance</h1>
        </div>
    </div>

    <div class="settings-grid">
        {{-- MAINTENANCE --}}
        <section class="form-card" aria-labelledby="maintenance-title">
            <div class="section-head">
                <div>
                    <h2 id="maintenance-title">Mode maintenance</h2>
                    <p class="form-hint">Les visiteurs voient une page d’attente (HTTP 503, sans pénalité pour le référencement). L’administration, les administrateurs connectés et les adresses IP autorisées continuent de voir le site.</p>
                </div>
                @if ($maintenance['enabled'])
                    <span class="tag tag-pending">En maintenance</span>
                @else
                    <span class="tag tag-success">Site en ligne</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.parameters.maintenance') }}" class="form-card" style="padding:0;border:0;background:none">
                @csrf
                @method('PUT')

                <label class="toggle-row">
                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $maintenance['enabled'])) data-maintenance-toggle>
                    <span>
                        <strong>Activer le mode maintenance</strong>
                        <small>Le site public sera immédiatement remplacé par la page de maintenance.</small>
                    </span>
                </label>

                <label>
                    Titre de la page
                    <input name="title" maxlength="120" value="{{ old('title', $maintenance['title']) }}" placeholder="Site en maintenance">
                </label>
                <label>
                    Message
                    <textarea name="message" rows="4" maxlength="1000" placeholder="Nous améliorons notre site. Merci de revenir un peu plus tard.">{{ old('message', $maintenance['message']) }}</textarea>
                </label>
                <label>
                    Date et heure de retour estimées (facultatif, heure de Guyane)
                    <input type="datetime-local" name="return_at" value="{{ old('return_at', $maintenance['return_at']) }}">
                </label>
                <label>
                    Adresses IP autorisées (une par ligne, plages CIDR acceptées)
                    <textarea name="allowed_ips" rows="3" placeholder="203.0.113.10" data-ip-list>{{ old('allowed_ips', $maintenance['allowed_ips']) }}</textarea>
                </label>
                <p class="form-hint">
                    Votre adresse IP actuelle : <strong>{{ $currentIp }}</strong>
                    <button type="button" class="link-button" data-add-ip="{{ $currentIp }}">Ajouter à la liste</button>
                </p>

                <div class="form-actions">
                    <a class="btn btn-secondary" href="{{ route('admin.parameters.maintenance.preview') }}" target="_blank" rel="noopener">Prévisualiser la page</a>
                    <button class="btn" type="submit">Enregistrer</button>
                </div>
            </form>
        </section>

        {{-- FAVICON --}}
        <section class="form-card" aria-labelledby="favicon-title">
            <div class="section-head">
                <div>
                    <h2 id="favicon-title">Favicon</h2>
                    <p class="form-hint">L’icône affichée dans l’onglet du navigateur, les favoris et sur l’écran d’accueil des téléphones. Envoyez une image carrée, idéalement <strong>512×512 px</strong>.</p>
                </div>
                @if ($favicon['version'])
                    <span class="tag tag-success">Personnalisé</span>
                @else
                    <span class="tag tag-muted">Par défaut</span>
                @endif
            </div>

            @if ($favicon['version'])
                <div class="favicon-sizes" aria-label="Icônes générées">
                    @foreach (\App\Services\FaviconGenerator::SIZES as $file => [$size])
                        <figure>
                            <img src="{{ Storage::disk('public')->url('favicon/'.$file) }}?v={{ $favicon['version'] }}" alt="" width="{{ min($size, 96) }}" height="{{ min($size, 96) }}">
                            <figcaption>{{ $size }} px</figcaption>
                        </figure>
                    @endforeach
                </div>
                <p class="form-hint">Générés : favicon.ico (16, 32, 48), PNG 16 et 32, apple-touch-icon 180, Android 192 et 512{{ $favicon['has_svg'] ? ', SVG' : '' }}, et le fichier <a href="{{ route('webmanifest') }}" target="_blank" rel="noopener">site.webmanifest</a>.</p>
            @endif

            <form method="POST" action="{{ route('admin.parameters.favicon') }}" enctype="multipart/form-data" class="form-card" style="padding:0;border:0;background:none">
                @csrf
                <label>
                    Nouvelle image (PNG{{ $svgSupported ? ' ou SVG' : '' }}, 2 Mo maximum)
                    <input type="file" name="favicon" accept="{{ $svgSupported ? '.png,.svg,image/png,image/svg+xml' : '.png,image/png' }}" required>
                </label>
                @unless ($svgSupported)
                    <p class="form-hint">Le SVG sera accepté une fois l’extension Imagick installée sur le serveur.</p>
                @endunless
                <div class="form-actions">
                    <button class="btn" type="submit">Générer le favicon</button>
                </div>
            </form>

            @if ($favicon['version'])
                <form method="POST" action="{{ route('admin.parameters.favicon.destroy') }}" onsubmit="return confirm('Revenir à l’icône par défaut ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Revenir à l’icône par défaut</button>
                </form>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var button = document.querySelector('[data-add-ip]');
            var list = document.querySelector('[data-ip-list]');
            button && button.addEventListener('click', function () {
                var ip = button.dataset.addIp;
                var lines = list.value.split(/\s+/).filter(Boolean);
                if (lines.indexOf(ip) === -1) { lines.push(ip); }
                list.value = lines.join('\n');
            });

            // Confirmation avant d'activer la maintenance.
            var form = document.querySelector('[data-maintenance-toggle]').form;
            var wasEnabled = document.querySelector('[data-maintenance-toggle]').checked;
            form.addEventListener('submit', function (event) {
                var enabled = document.querySelector('[data-maintenance-toggle]').checked;
                if (enabled && !wasEnabled && !confirm('Activer le mode maintenance ? Les visiteurs ne verront plus le site.')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
@endpush
