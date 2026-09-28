@extends('admin.layout')

@section('title', 'Clients')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Clients</h1>
        </div>
        <div class="form-actions">
            <a class="btn" href="{{ route('admin.customers.create') }}">+ Nouveau client</a>
            <form class="inline-filter filter-bar" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, société, email, téléphone…" aria-label="Rechercher un client">
                <select name="type" aria-label="Filtrer par type" onchange="this.form.submit()">
                    <option value="">Tous les clients</option>
                    @foreach (\App\Models\Customer::TYPES as $type => $label)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}s</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @include('admin.customers._tabs')

    @if ($duplicateCount)
        <div class="maintenance-banner" role="note">
            <span>{{ $duplicateCount }} fiche(s) ressemblent à d’autres (même email, téléphone ou nom).</span>
            <a href="{{ route('admin.customers.duplicates') }}">Vérifier les doublons</a>
        </div>
    @endif

    <div class="table-card">
        <table>
            <thead>
                <tr><th>Client</th><th>Contact</th><th>Ville</th><th>Demandes</th><th>Devis</th><th>Origine</th></tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <a href="{{ route('admin.customers.edit', $customer) }}"><strong>{{ $customer->display_name }}</strong></a>
                            <span>{{ \App\Models\Customer::TYPES[$customer->type] ?? $customer->type }}</span>
                        </td>
                        <td>{{ $customer->email ?: '—' }}<span>{{ collect([$customer->phone_mobile, $customer->phone_landline])->filter()->implode(' · ') }}</span></td>
                        <td>{{ $customer->city ?: '—' }}</td>
                        <td>{{ $customer->reservations_count }}</td>
                        <td>{{ $customer->quotes_count }}</td>
                        <td><span class="tag tag-muted">{{ \App\Models\Customer::SOURCES[$customer->source] ?? $customer->source }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ request('q') ? 'Aucun client ne correspond à la recherche.' : 'Aucun client pour le moment : ils sont créés automatiquement à chaque demande du site.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $customers->links() }}
@endsection
