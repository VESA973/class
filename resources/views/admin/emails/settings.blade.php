@extends('admin.layout')

@section('title', 'Emails')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Communication</p>
            <h1>Emails</h1>
        </div>
        <p class="form-hint">30 derniers jours : <strong>{{ $stats['sent'] }}</strong> envoyé(s) · <strong @if ($stats['failed']) style="color:var(--danger)" @endif>{{ $stats['failed'] }}</strong> échec(s)</p>
    </div>

    @include('admin.emails._tabs')

    <div class="settings-grid">
        <form class="form-card" method="POST" action="{{ route('admin.emails.settings.update') }}" aria-labelledby="smtp-title">
            @csrf
            @method('PUT')
            <div>
                <h2 id="smtp-title">Serveur d’envoi</h2>
                <p class="form-hint">Par défaut, le site utilise les réglages du fichier <code>.env</code> du serveur (actuellement : <strong>{{ $envMailer }}</strong>). Vous pouvez saisir ici votre propre serveur SMTP (celui de votre hébergeur ou de votre messagerie).</p>
            </div>

            <fieldset class="choice-group">
                <legend class="sr-only">Mode d’envoi</legend>
                <label class="toggle-row">
                    <input type="radio" name="mode" value="env" @checked(old('mode', $values['mode']) === 'env') data-mail-mode>
                    <span><strong>Réglages du serveur (.env)</strong><small>Recommandé si l’hébergeur a déjà configuré l’envoi d’emails.</small></span>
                </label>
                <label class="toggle-row">
                    <input type="radio" name="mode" value="smtp" @checked(old('mode', $values['mode']) === 'smtp') data-mail-mode>
                    <span><strong>Serveur SMTP personnalisé</strong><small>Les paramètres ci-dessous remplacent ceux du .env.</small></span>
                </label>
            </fieldset>

            <div class="form-grid" data-smtp-fields>
                <label>Serveur SMTP (hôte)
                    <input name="host" value="{{ old('host', $values['host']) }}" placeholder="ssl0.ovh.net" autocomplete="off">
                </label>
                <label>Port
                    <input type="number" name="port" value="{{ old('port', $values['port']) }}" min="1" max="65535" placeholder="587">
                </label>
                <label>Chiffrement
                    <select name="encryption">
                        @foreach ($encryptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('encryption', $values['encryption']) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Identifiant
                    <input name="username" value="{{ old('username', $values['username']) }}" autocomplete="off" placeholder="reservation@votre-domaine.fr">
                </label>
                <label>Mot de passe
                    <input type="password" name="password" autocomplete="new-password" placeholder="{{ $values['has_password'] ? '•••••••• (enregistré — laisser vide pour le garder)' : 'Mot de passe SMTP' }}">
                </label>
                @if ($values['has_password'])
                    <div class="checkboxes"><label><input type="checkbox" name="clear_password" value="1"> Effacer le mot de passe enregistré</label></div>
                @endif
            </div>
            <p class="form-hint">Le mot de passe est chiffré avant d’être enregistré et n’est jamais réaffiché.</p>

            <hr class="divider">

            <div class="form-grid">
                <label>Adresse d’expédition
                    <input type="email" name="from_address" value="{{ old('from_address', $values['from_address']) }}" required>
                </label>
                <label>Nom d’expéditeur
                    <input name="from_name" value="{{ old('from_name', $values['from_name']) }}" placeholder="CLASS’AFFAIRE">
                </label>
                <label>Adresse qui reçoit les nouvelles demandes
                    <input type="email" name="admin_email" value="{{ old('admin_email', $values['admin_email']) }}" placeholder="vous@votre-domaine.fr">
                </label>
            </div>

            <label class="toggle-row">
                <input type="checkbox" name="use_queue" value="1" @checked(old('use_queue', $values['use_queue']))>
                <span><strong>Envoyer via la file d’attente</strong><small>À activer seulement si un worker (<code>php artisan queue:work</code>) tourne sur le serveur. Sinon, les emails partent juste après la réponse au visiteur.</small></span>
            </label>

            <div class="form-actions">
                <button class="btn" type="submit">Enregistrer</button>
            </div>
        </form>

        <div class="form-card" style="gap:16px">
            <form method="POST" action="{{ route('admin.emails.test') }}" class="form-card" style="padding:0;border:0;background:none">
                @csrf
                <div>
                    <h2>Envoyer un email de test</h2>
                    <p class="form-hint">Vérifie la configuration enregistrée. Le résultat apparaît aussi dans l’historique.</p>
                </div>
                <label>Adresse de destination
                    <input type="email" name="to" value="{{ old('to', auth()->user()->email) }}" required>
                </label>
                <div class="form-actions"><button class="btn btn-secondary" type="submit">Envoyer le test</button></div>
            </form>

            <hr class="divider">

            <div>
                <h2>Réglages courants</h2>
                <ul class="plain-list">
                    <li><strong>OVH</strong> : ssl0.ovh.net · port 465 · SSL/TLS</li>
                    <li><strong>Gmail / Google Workspace</strong> : smtp.gmail.com · port 587 · STARTTLS (mot de passe d’application)</li>
                    <li><strong>Outlook / Microsoft 365</strong> : smtp.office365.com · port 587 · STARTTLS</li>
                    <li><strong>Brevo</strong> : smtp-relay.brevo.com · port 587 · STARTTLS</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Champs SMTP actifs seulement en mode "serveur personnalise".
        (function () {
            var fields = document.querySelector('[data-smtp-fields]');
            function sync() {
                var smtp = document.querySelector('[data-mail-mode]:checked').value === 'smtp';
                fields.style.opacity = smtp ? 1 : .5;
                fields.querySelectorAll('input, select').forEach(function (el) { el.disabled = !smtp; });
            }
            document.querySelectorAll('[data-mail-mode]').forEach(function (el) { el.addEventListener('change', sync); });
            sync();
        })();
    </script>
@endpush
