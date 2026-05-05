@extends('admin.layout')

@section('title', 'Vehicules')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Base de donnees</p>
            <h1>Vehicules</h1>
        </div>
        <a class="btn" href="{{ route('admin.vehicles.create') }}">Ajouter un vehicule</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Modele</th>
                    <th>Categorie</th>
                    <th>Chevaux</th>
                    <th>Prix/jour</th>
                    <th>Etat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td><img class="thumb" src="{{ $vehicle->display_image }}" alt="{{ $vehicle->name }}"></td>
                        <td>
                            <strong>{{ $vehicle->name }}</strong>
                            @if ($vehicle->with_chauffeur)
                                <span class="tag">Chauffeur</span>
                            @endif
                        </td>
                        <td>{{ $vehicle->category }}</td>
                        <td>{{ $vehicle->horsepower ? $vehicle->horsepower.' ch' : '-' }}</td>
                        <td>{{ number_format($vehicle->daily_price, 0, ',', ' ') }} EUR</td>
                        <td>{{ $vehicle->is_available ? 'Disponible' : 'Indisponible' }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.vehicles.edit', $vehicle) }}">Modifier</a>
                            <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Aucun vehicule pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $vehicles->links() }}
@endsection
