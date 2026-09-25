@extends('admin.layout')

@section('title', 'Registre des consentements')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Site</p>
            <h1>Cookies</h1>
        </div>
        <form class="inline-filter" method="GET">
            <select name="action" aria-label="Filtrer par choix" onchange="this.form.submit()">
                <option value="">Tous les choix</option>
                @foreach (\App\Models\CookieConsent::ACTIONS as $key => $label)
                    <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @include('admin.cookies._tabs')

    <div class="stat-grid">
        <div class="stat-card"><span class="stat-card-head">Choix (30 jours)</span><span class="stat-value">{{ $total }}</span><span class="stat-hint">Version actuelle de la politique : {{ $version }}</span></div>
        @foreach (\App\Models\CookieConsent::ACTIONS as $key => $label)
            <div class="stat-card">
                <span class="stat-card-head">{{ $label }}</span>
                <span class="stat-value">{{ $total ? round(($stats[$key] ?? 0) * 100 / $total) : 0 }} %</span>
                <span class="stat-hint">{{ $stats[$key] ?? 0 }} choix</span>
            </div>
        @endforeach
    </div>

    <p class="form-hint" style="margin-bottom:14px">Preuve des consentements : identifiant aléatoire du navigateur, choix et date. Aucune adresse IP ni information sur l’appareil n’est enregistrée. Les preuves sont supprimées automatiquement après 13 mois.</p>

    <div class="table-card">
        <table>
            <thead><tr><th>Date</th><th>Identifiant</th><th>Choix</th><th>Mesure d’audience</th><th>Marketing</th><th>Version</th></tr></thead>
            <tbody>
                @forelse ($consents as $consent)
                    <tr>
                        <td>{{ $consent->created_at->timezone(config('app.local_timezone'))->format('d/m/Y H:i') }}</td>
                        <td><code>{{ \Illuminate\Support\Str::limit($consent->consent_id, 13, '…') }}</code></td>
                        <td><span class="tag {{ ['accept_all' => 'tag-success', 'reject_all' => 'tag-danger', 'custom' => 'tag-pending'][$consent->action] }}">{{ \App\Models\CookieConsent::ACTIONS[$consent->action] }}</span></td>
                        <td>{{ $consent->analytics ? 'Oui' : 'Non' }}</td>
                        <td>{{ $consent->marketing ? 'Oui' : 'Non' }}</td>
                        <td>{{ $consent->policy_version }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Aucun choix enregistré pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $consents->links() }}
@endsection
