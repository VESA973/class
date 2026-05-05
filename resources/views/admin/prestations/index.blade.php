@extends('admin.layout')

@section('title', 'Prestations')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Evenements et transferts</p>
            <h1>Prestations</h1>
        </div>
        <a class="btn" href="{{ route('admin.prestations.create') }}">Ajouter une prestation</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Ordre</th>
                    <th>Etat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prestations as $prestation)
                    <tr>
                        <td><img class="thumb" src="{{ $prestation->display_image }}" alt="{{ $prestation->name }}"></td>
                        <td>
                            <strong>{{ $prestation->name }}</strong>
                            <span>{{ $prestation->description ?: 'Aucune description' }}</span>
                        </td>
                        <td>{{ $prestation->sort_order }}</td>
                        <td>{{ $prestation->is_active ? 'Active' : 'Masquee' }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.prestations.edit', $prestation) }}">Modifier</a>
                            <form method="POST" action="{{ route('admin.prestations.destroy', $prestation) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aucune prestation pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $prestations->links() }}
@endsection
