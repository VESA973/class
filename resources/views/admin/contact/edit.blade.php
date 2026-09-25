@extends('admin.layout')

@section('title', 'Coordonnées & WhatsApp')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Paramètres</p>
            <h1>Coordonnées & WhatsApp</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.contact.update') }}" class="settings-grid">
        @csrf
        @method('PUT')

        <section class="form-card" aria-labelledby="contact-title">
            <div class="section-head">
                <div>
                    <h2 id="contact-title">Coordonnées</h2>
                    <p class="form-hint">Affichées dans l’en-tête, le pied de page, la page Contact, la page de réservation, les emails, les devis, les pages légales et les données SEO.</p>
                </div>
            </div>

            <label>
                Téléphone
                <input name="phone" type="tel" maxlength="40" required value="{{ old('phone', $values['phone']) }}" placeholder="+33 1 80 11 44 83">
            </label>
            <p class="form-hint">Écrivez-le comme il doit s’afficher. Le lien d’appel (tel:) est généré automatiquement : <strong>{{ $values['phone_href'] }}</strong></p>
            <label>
                Email de contact
                <input name="email" type="email" maxlength="255" required value="{{ old('email', $values['email']) }}">
            </label>
            <label>
                Adresse
                <input name="address" maxlength="255" required value="{{ old('address', $values['address']) }}">
            </label>
        </section>

        <section class="form-card" aria-labelledby="whatsapp-title">
            <div class="section-head">
                <div>
                    <h2 id="whatsapp-title">Bouton WhatsApp</h2>
                    <p class="form-hint">Un bouton rond vert, en bas à droite de toutes les pages du site, ouvre une conversation WhatsApp avec ce numéro (application sur mobile, WhatsApp Web sur ordinateur).</p>
                </div>
                @if ($whatsappUrl)
                    <span class="tag tag-success">Affiché</span>
                @else
                    <span class="tag tag-muted">Masqué</span>
                @endif
            </div>

            <label class="toggle-row">
                <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $values['whatsapp_enabled']))>
                <span>
                    <strong>Afficher le bouton WhatsApp</strong>
                    <small>Le numéro doit être un compte WhatsApp (mobile ou WhatsApp Business).</small>
                </span>
            </label>
            <label>
                Numéro WhatsApp
                <input name="whatsapp_number" type="tel" maxlength="40" value="{{ old('whatsapp_number', $values['whatsapp_number'] ? '+'.$values['whatsapp_number'] : '') }}" placeholder="+33 6 12 34 56 78">
            </label>
            <p class="form-hint">Format international conseillé (+33…). Un numéro français en 06 / 07 est converti automatiquement.</p>
            <label>
                Message pré-rempli
                <textarea name="whatsapp_message" rows="3" maxlength="500">{{ old('whatsapp_message', $values['whatsapp_message']) }}</textarea>
            </label>
            <p class="form-hint">Le client peut le modifier avant l’envoi. Sur la fiche d’un véhicule, le nom du véhicule est ajouté automatiquement.</p>

            @if ($whatsappUrl)
                <p class="form-hint"><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Tester le lien WhatsApp</a></p>
            @endif
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection
