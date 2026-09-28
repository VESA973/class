@extends('admin.layout')

@section('title', 'Services & forfaits des devis')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Devis</h1>
        </div>
    </div>

    @include('admin.quotes._tabs')

    <p class="form-hint" style="margin-bottom:14px">
        Vos services et forfaits (chauffeur, heures supplémentaires, livraison, forfait journée…). Ils s’ajoutent en un clic dans un devis avec « + Ajouter un service ».
        Avec un <strong>seuil d’heures</strong>, le service est ajouté <strong>automatiquement</strong> aux devis créés depuis une demande dont la location dépasse ce seuil
        (ex. « Heures supplémentaires », par heure, au-delà de 7 h : une location de 9 h ajoute 2 h).
        Prix {{ $config['vat_enabled'] ? ($config['prices_include_vat'] ? 'TTC (convertis en HT sur le devis)' : 'HT') : 'nets (TVA désactivée)' }}.
    </p>

    <form method="POST" action="{{ route('admin.quotes.services.update') }}" class="grid-stack" data-repeater>
        @csrf
        @method('PUT')

        @php($rows = old('services', $services))
        <div class="repeater-list" data-repeater-list>
            @foreach ($rows as $index => $service)
                @include('admin.quotes._service-row', ['index' => $index, 'service' => $service])
            @endforeach
        </div>

        <template data-repeater-template>
            @include('admin.quotes._service-row', ['index' => '__INDEX__', 'service' => ['name' => '', 'price' => '', 'unit' => 'forfait', 'auto_after_hours' => null]])
        </template>

        <p class="form-hint" data-repeater-empty @if (count($rows)) hidden @endif>Aucun service pour le moment : ajoutez votre premier forfait.</p>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-repeater-add data-max="50">Ajouter un service</button>
            <button class="btn" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection

@include('admin.content._repeater-script')
