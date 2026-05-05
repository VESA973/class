<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration') - CLASS’AFFAIRE</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <aside class="admin-sidebar">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">CLASS’AFFAIRE</a>
        <nav>
            <a href="{{ route('admin.vehicles.index') }}">Vehicules</a>
            <a href="{{ route('admin.reservations.index') }}">Reservations</a>
            <a href="{{ route('home') }}">Voir le site</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Deconnexion</button>
            </form>
        </nav>
    </aside>

    <main class="admin-main">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
