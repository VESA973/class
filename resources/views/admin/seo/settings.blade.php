@extends('admin.layout')

@section('title', 'Réglages SEO')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>SEO</h1>
        </div>
    </div>

    @include('admin.seo._tabs')

    <form method="POST" action="{{ route('admin.seo.settings.update') }}" enctype="multipart/form-data" class="settings-grid">
        @csrf
        @method('PUT')

        <section class="form-card">
            <h2>Valeurs par défaut</h2>
            <label>Nom du site<input name="site_name" value="{{ old('site_name', $defaults['site_name']) }}" required maxlength="80"></label>
            <label>Description par défaut<textarea name="default_description" rows="3" maxlength="300">{{ old('default_description', $defaults['default_description']) }}</textarea></label>
            <label>Image de partage par défaut (1200×630 conseillé)
                <input type="file" name="default_og_image" accept="image/*">
                <span class="form-hint">Sans image, la photo d’accueil est utilisée.</span>
            </label>
            @if ($defaults['default_og_image'])
                <img src="{{ Storage::disk('public')->url($defaults['default_og_image']) }}" alt="" class="thumb" style="width:200px;height:105px">
            @endif

            <hr class="divider">
            <h2>robots.txt</h2>
            <label>Contenu
                <textarea name="robots_txt" rows="6" class="mono" maxlength="5000">{{ old('robots_txt', $defaults['robots_txt']) }}</textarea>
                <span class="form-hint">La ligne « Sitemap: {{ route('sitemap') }} » est ajoutée automatiquement.</span>
            </label>
        </section>

        <section class="form-card">
            <h2>Données structurées (schema.org)</h2>
            <p class="form-hint">Décrivent votre entreprise à Google (fiche locale, résultats enrichis). Présentes sur toutes les pages publiques.</p>
            <div class="form-grid">
                <label>Type d’activité
                    <select name="business[type]">
                        @foreach (['AutoRental' => 'Location de véhicules (AutoRental)', 'TaxiService' => 'Service de chauffeur (TaxiService)', 'LocalBusiness' => 'Entreprise locale', 'Organization' => 'Organisation'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('business.type', $defaults['business']['type']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Nom<input name="business[name]" value="{{ old('business.name', $defaults['business']['name']) }}" required></label>
                <label>Téléphone<input name="business[telephone]" value="{{ old('business.telephone', $defaults['business']['telephone']) }}"></label>
                <label>Email<input type="email" name="business[email]" value="{{ old('business.email', $defaults['business']['email']) }}"></label>
                <label>Adresse<input name="business[street]" value="{{ old('business.street', $defaults['business']['street']) }}"></label>
                <label>Code postal<input name="business[postal_code]" value="{{ old('business.postal_code', $defaults['business']['postal_code']) }}"></label>
                <label>Ville<input name="business[city]" value="{{ old('business.city', $defaults['business']['city']) }}"></label>
                <label>Zones desservies (séparées par des virgules)<input name="business[area_served]" value="{{ old('business.area_served', $defaults['business']['area_served']) }}"></label>
                <label>Gamme de prix<input name="business[price_range]" value="{{ old('business.price_range', $defaults['business']['price_range']) }}" placeholder="€€€€"></label>
                <label>Horaires (format schema.org)<input name="business[opening_hours]" value="{{ old('business.opening_hours', $defaults['business']['opening_hours']) }}" placeholder="Mo-Su 00:00-23:59"></label>
            </div>
            <label>Réseaux sociaux (une adresse par ligne)<textarea name="business[same_as]" rows="3" placeholder="https://www.instagram.com/…">{{ old('business.same_as', $defaults['business']['same_as']) }}</textarea></label>
            <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
        </section>
    </form>
@endsection
