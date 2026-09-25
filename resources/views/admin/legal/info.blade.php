@extends('admin.layout')

@section('title', 'Informations légales')

@php
    $groups = [
        'Entreprise' => ['raison_sociale', 'forme_juridique', 'capital', 'rcs', 'siret', 'tva_intracom', 'adresse_siege', 'telephone', 'email'],
        'Publication et hébergement' => ['directeur_publication', 'hebergeur_nom', 'hebergeur_adresse', 'hebergeur_telephone'],
        'Données personnelles et consommateurs' => ['contact_rgpd', 'mediateur_nom', 'mediateur_site'],
        'Durées de conservation' => ['duree_conservation_demandes', 'duree_conservation_devis', 'duree_conservation_emails', 'duree_conservation_consentements'],
    ];
    $placeholders = [
        'forme_juridique' => 'SAS, SARL…', 'capital' => '10 000 €', 'rcs' => 'Pontoise', 'siret' => '123 456 789 00012', 'tva_intracom' => 'FR12 123456789',
        'directeur_publication' => 'Prénom Nom, Président', 'hebergeur_nom' => 'OVH SAS', 'hebergeur_adresse' => '2 rue Kellermann, 59100 Roubaix, France',
        'hebergeur_telephone' => '1007', 'contact_rgpd' => 'rgpd@votre-domaine.fr', 'mediateur_nom' => 'Nom du médiateur', 'mediateur_site' => 'https://…',
    ];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Pages légales</p>
            <h1>Informations de l’entreprise</h1>
        </div>
    </div>

    @include('admin.legal._tabs')

    <form method="POST" action="{{ route('admin.legal.info.update') }}" class="settings-grid">
        @csrf
        @method('PUT')
        @foreach ($groups as $title => $fields)
            <section class="form-card">
                <h2>{{ $title }}</h2>
                @if ($title === 'Entreprise')
                    <p class="form-hint">Également utilisées sur les devis (Devis › Réglages).</p>
                @elseif ($title === 'Durées de conservation')
                    <p class="form-hint">Valeurs proposées par défaut, à adapter à vos pratiques.</p>
                @elseif ($title === 'Données personnelles et consommateurs')
                    <p class="form-hint">L’adhésion à un médiateur de la consommation est obligatoire pour vendre à des particuliers.</p>
                @endif
                @foreach ($fields as $field)
                    <label>{{ $variables[$field][0] }} <code style="color:var(--muted)">{{ '{'.$field.'}' }}</code>
                        @if (in_array($field, ['adresse_siege', 'hebergeur_adresse'], true))
                            <textarea name="{{ $field }}" rows="2" maxlength="500">{{ old($field, $values[$field]) }}</textarea>
                        @else
                            <input name="{{ $field }}" value="{{ old($field, $values[$field]) }}" maxlength="500" placeholder="{{ $placeholders[$field] ?? '' }}" @if ($values[$field] === '') style="border-color:rgba(251,191,36,.5)" @endif>
                        @endif
                    </label>
                @endforeach
            </section>
        @endforeach
        <div class="form-actions" style="grid-column:1/-1"><button class="btn" type="submit">Enregistrer les informations</button></div>
    </form>
@endsection
