<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion admin - Prestige Drive</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
    <main class="login-card">
        <p class="eyebrow">Administration</p>
        <h1>Connexion</h1>

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <label>
                Mot de passe
                <input type="password" name="password" required autofocus>
            </label>
            <button class="btn" type="submit">Entrer</button>
        </form>
    </main>
</body>
</html>
