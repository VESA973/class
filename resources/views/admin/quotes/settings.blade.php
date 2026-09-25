@extends('admin.layout')

@section('title', 'Réglages des devis')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Devis</h1>
        </div>
    </div>

    @include('admin.quotes._tabs')

    <form method="POST" action="{{ route('admin.quotes.settings.update') }}" class="settings-grid">
        @csrf
        @method('PUT')

        <section class="form-card" aria-labelledby="default-title">
            <div>
                <h2 id="default-title">Modèle de devis par défaut</h2>
                <p class="form-hint">Utilisé pour pré-remplir chaque nouveau devis créé depuis une demande.</p>
            </div>
            <div class="form-grid">
                <label>TVA par défaut
                    <select name="vat_rate">
                        @foreach ($vatRates as $rate => $label)
                            <option value="{{ $rate }}" @selected(old('vat_rate', $config['vat_rate']) == $rate)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Durée de validité (jours)
                    <input type="number" name="validity_days" min="1" max="365" value="{{ old('validity_days', $config['validity_days']) }}" required>
                </label>
            </div>
            <label>Ligne par défaut (tarif du véhicule × nombre de jours)
                <input name="line_template" maxlength="500" value="{{ old('line_template', $config['line_template']) }}" required>
                <span class="form-hint">Variables : {vehicule}, {date_depart}, {date_retour}, {lieu_depart}, {destination}, {passagers}</span>
            </label>
            <div class="checkboxes">
                <label><input type="checkbox" name="prices_include_vat" value="1" @checked(old('prices_include_vat', $config['prices_include_vat']))> Les prix par jour des véhicules sont TTC (convertis en HT sur le devis)</label>
            </div>
            <label>Conditions par défaut
                <textarea name="conditions" rows="4" maxlength="5000">{{ old('conditions', $config['conditions']) }}</textarea>
            </label>

            <hr class="divider">

            <div class="auto-send">
                <label class="toggle-row">
                    <input type="checkbox" name="auto_send" value="1" @checked(old('auto_send', $config['auto_send'])) data-auto-send>
                    <span>
                        <strong>Envoi automatique du devis après chaque demande</strong>
                        <small>⚠️ Désactivé par défaut. Activé, chaque demande reçue sur le site génère un devis à partir du modèle ci-dessus (tarif du véhicule × durée) et l’envoie <strong>immédiatement au client, sans relecture</strong>. Vérifiez les tarifs, la TVA, les conditions et les informations de l’entreprise avant de l’activer.</small>
                    </span>
                </label>
            </div>
        </section>

        <section class="form-card" aria-labelledby="company-title">
            <div>
                <h2 id="company-title">Informations de l’entreprise (PDF)</h2>
                <p class="form-hint">Mentions affichées sur chaque devis. Les champs vides apparaissent en « À compléter » sur le PDF.</p>
            </div>
            <div class="form-grid">
                <label>Raison sociale<input name="company[name]" value="{{ old('company.name', $config['company']['name']) }}" placeholder="CLASS’AFFAIRE SAS"></label>
                <label>Forme juridique et capital<input name="company[legal_form]" value="{{ old('company.legal_form', $config['company']['legal_form']) }}" placeholder="SAS au capital de 10 000 €"></label>
                <label>SIRET<input name="company[siret]" value="{{ old('company.siret', $config['company']['siret']) }}" placeholder="123 456 789 00012"></label>
                <label>N° de TVA intracommunautaire<input name="company[vat_number]" value="{{ old('company.vat_number', $config['company']['vat_number']) }}" placeholder="FR12 123456789"></label>
                <label>Email<input type="email" name="company[email]" value="{{ old('company.email', $config['company']['email']) }}"></label>
                <label>Téléphone<input name="company[phone]" value="{{ old('company.phone', $config['company']['phone']) }}"></label>
            </div>
            <label>Adresse du siège<textarea name="company[address]" rows="2" maxlength="500">{{ old('company.address', $config['company']['address']) }}</textarea></label>
            <label>IBAN (facultatif, pour le règlement)<input name="company[iban]" value="{{ old('company.iban', $config['company']['iban']) }}"></label>
            <div class="form-actions"><button class="btn" type="submit">Enregistrer les réglages</button></div>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var box = document.querySelector('[data-auto-send]');
            var initial = box.checked;
            box.form.addEventListener('submit', function (event) {
                if (box.checked && !initial && !confirm('Activer l’envoi AUTOMATIQUE des devis ? Chaque nouvelle demande recevra un devis sans relecture.')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
@endpush
