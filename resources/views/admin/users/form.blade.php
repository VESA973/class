@extends('admin.layout')

@section('title', $user->exists ? 'Modifier utilisateur' : 'Ajouter utilisateur')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $user->exists ? 'Modification' : 'Creation' }}</p>
            <h1>{{ $user->exists ? $user->name : 'Nouvel utilisateur' }}</h1>
        </div>
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Retour</a>
    </div>

    <form class="form-card" method="POST" action="{{ $action }}">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="form-grid">
            <label>
                Nom
                <input name="name" value="{{ old('name', $user->name) }}" required>
            </label>
            <label>
                Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="off" required>
            </label>
            <label>
                Mot de passe
                <input type="password" name="password" autocomplete="new-password" @required(! $user->exists)>
            </label>
            <label>
                Confirmation du mot de passe
                <input type="password" name="password_confirmation" autocomplete="new-password" @required(! $user->exists)>
            </label>
        </div>

        <p class="form-hint">
            8 caracteres minimum.
            @if ($user->exists)
                Laissez vide pour conserver le mot de passe actuel.
            @endif
        </p>

        <div class="checkboxes">
            <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Compte actif (peut se connecter)</label>
        </div>

        <button class="btn" type="submit">Enregistrer</button>
    </form>
@endsection
