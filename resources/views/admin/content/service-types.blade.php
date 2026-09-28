@extends('admin.layout')

@section('title', 'Types de prestation')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & devis</p>
            <h1>Types de prestation</h1>
            <p class="form-hint">Proposés dans le module de réservation de l’accueil et sur la page Réserver. Le type choisi par le client apparaît dans la demande, le planning, les emails et le devis.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('booking.create') }}" target="_blank" rel="noopener">Voir la page Réserver</a>
    </div>

    <form method="POST" action="{{ route('admin.content.service-types.update') }}" class="form-card" data-repeater>
        @csrf
        @method('PUT')

        @php($rows = old('types', $types))
        <div class="repeater-list" data-repeater-list>
            @foreach ($rows as $type)
                @include('admin.content._service-type-row', ['type' => $type])
            @endforeach
        </div>

        <template data-repeater-template>
            @include('admin.content._service-type-row', ['type' => ''])
        </template>

        <p class="form-hint" data-repeater-empty @if (count($rows)) hidden @endif>Aucun type : le champ « Type de prestation » n’est pas affiché aux clients.</p>
        <p class="form-hint">Retirer un type ne modifie pas les demandes déjà reçues.</p>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-repeater-add data-max="{{ \App\Services\SiteContent::MAX_SERVICE_TYPES }}">Ajouter un type</button>
            <button class="btn" type="submit">Enregistrer</button>
        </div>
    </form>
@endsection

@include('admin.content._repeater-script')
