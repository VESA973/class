@extends('admin.layout')

@section('title', $vehicle->exists ? 'Modifier vehicule' : 'Ajouter vehicule')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $vehicle->exists ? 'Modification' : 'Creation' }}</p>
            <h1>{{ $vehicle->exists ? $vehicle->name : 'Nouveau vehicule' }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.vehicles.index') }}">Retour</a>
    </div>

    <form class="form-card" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="form-grid">
            <label>
                Nom du modele
                <input name="name" value="{{ old('name', $vehicle->name) }}" required>
            </label>
            <label>
                Categorie
                <input name="category" list="categories" value="{{ old('category', $vehicle->category) }}" required>
                <datalist id="categories">
                    <option value="SUV">
                    <option value="Supercar">
                    <option value="Berline">
                    <option value="Chauffeur">
                </datalist>
            </label>
            <label>
                Type de chevaux
                <input type="number" min="1" name="horsepower" value="{{ old('horsepower', $vehicle->horsepower) }}" placeholder="641">
            </label>
            <label>
                Prix estimatif par jour
                <input type="number" min="1" name="daily_price" value="{{ old('daily_price', $vehicle->daily_price) }}" required>
            </label>
            <label>
                Carburant
                <input name="fuel_type" value="{{ old('fuel_type', $vehicle->fuel_type) }}" required>
            </label>
            <label>
                Transmission
                <input name="transmission" value="{{ old('transmission', $vehicle->transmission) }}" required>
            </label>
            <label>
                Image a uploader
                <input type="file" name="image" accept="image/*">
            </label>
            <label>
                URL image externe
                <input type="url" name="image_url" value="{{ old('image_url', $vehicle->image_url) }}" placeholder="https://...">
            </label>
        </div>

        @if ($vehicle->exists)
            <img class="preview" src="{{ $vehicle->display_image }}" alt="{{ $vehicle->name }}">
        @endif

        <label>
            Description
            <textarea name="description" rows="5">{{ old('description', $vehicle->description) }}</textarea>
        </label>

        <div class="checkboxes">
            <label><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $vehicle->is_available))> Disponible</label>
            <label><input type="checkbox" name="with_chauffeur" value="1" @checked(old('with_chauffeur', $vehicle->with_chauffeur))> Avec chauffeur</label>
        </div>

        <button class="btn" type="submit">Enregistrer</button>
    </form>
@endsection
