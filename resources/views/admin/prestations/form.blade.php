@extends('admin.layout')

@section('title', $prestation->exists ? 'Modifier prestation' : 'Ajouter prestation')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $prestation->exists ? 'Modification' : 'Creation' }}</p>
            <h1>{{ $prestation->exists ? $prestation->name : 'Nouvelle prestation' }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.prestations.index') }}">Retour</a>
    </div>

    <form class="form-card" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="form-grid">
            <label>
                Nom de la prestation
                <input name="name" value="{{ old('name', $prestation->name) }}" placeholder="Mariage, Transfert aeroport..." required>
            </label>
            <label>
                Ordre d'affichage
                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $prestation->sort_order) }}" required>
            </label>
            <label>
                Image a uploader
                <input type="file" name="image" accept="image/*">
            </label>
            <label>
                URL image externe
                <input type="url" name="image_url" value="{{ old('image_url', $prestation->image_url) }}" placeholder="https://...">
            </label>
        </div>

        @if ($prestation->exists)
            <img class="preview" src="{{ $prestation->display_image }}" alt="{{ $prestation->name }}">
        @endif

        <label>
            Description courte
            <textarea name="description" rows="5">{{ old('description', $prestation->description) }}</textarea>
        </label>

        <div class="checkboxes">
            <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $prestation->is_active))> Afficher sur le site</label>
        </div>

        <button class="btn" type="submit">Enregistrer</button>
    </form>
@endsection
