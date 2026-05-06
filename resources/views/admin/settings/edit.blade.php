@extends('admin.layout')

@section('title', 'Logo du site')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Identité</p>
            <h1>Logo du site</h1>
        </div>
    </div>

    <form class="form-card compact" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if ($settings->logo_url)
            <div>
                <p class="field-note">Logo actuel</p>
                <img class="logo-preview" src="{{ $settings->logo_url }}" alt="Logo actuel">
            </div>
        @endif

        <label>
            Nouveau logo
            <input type="file" name="logo" accept="image/*">
        </label>

        <button class="btn" type="submit">Enregistrer le logo</button>
    </form>

    @if ($settings->logo_url)
        <form class="form-card compact danger-zone" method="POST" action="{{ route('admin.settings.logo.destroy') }}">
            @csrf
            @method('DELETE')
            <p>Supprimer le logo actuel et revenir au monogramme CA.</p>
            <button class="btn btn-secondary" type="submit">Supprimer le logo</button>
        </form>
    @endif
@endsection
