@extends('admin.layout')

@section('title', 'Utilisateurs')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Acces a l'administration</p>
            <h1>Utilisateurs</h1>
        </div>
        <a class="btn" href="{{ route('admin.users.create') }}">Ajouter un utilisateur</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Etat</th>
                    <th>Cree le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if ($user->is(auth()->user()))
                                <span>Vous</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->is_active ? 'Actif' : 'Desactive' }}</td>
                        <td>{{ $user->created_at?->format('d/m/Y') }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.users.edit', $user) }}">Modifier</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Supprimer</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aucun utilisateur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
