<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion admin - CLASS’AFFAIRE</title>
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
                Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </label>
            <label>
                Mot de passe
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Rester connecte
            </label>
            <button class="btn" type="submit">Entrer</button>
        </form>
    </main>
</body>
</html>
