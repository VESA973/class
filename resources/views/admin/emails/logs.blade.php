@extends('admin.layout')

@section('title', 'Historique des emails')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Communication</p>
            <h1>Emails</h1>
        </div>
        <form class="inline-filter filter-bar" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Destinataire ou objet…" aria-label="Rechercher">
            <select name="status" aria-label="Filtrer par statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach (\App\Models\EmailLog::STATUS_LABELS as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @include('admin.emails._tabs')

    <div class="table-card">
        <table>
            <thead>
                <tr><th>Date</th><th>Destinataire</th><th>Objet</th><th>Type</th><th>Statut</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->timezone('Europe/Paris')->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->recipient }}</td>
                        <td>
                            {{ $log->subject }}
                            @if ($log->reservation_id)
                                <span><a href="{{ route('admin.reservations.show', $log->reservation_id) }}">Réservation n°{{ $log->reservation_id }}</a></span>
                            @endif
                        </td>
                        <td>{{ $templateNames[$log->template_key] ?? ($log->template_key ?: '—') }}</td>
                        <td>
                            <span class="tag {{ ['sent' => 'tag-success', 'failed' => 'tag-danger', 'queued' => 'tag-pending'][$log->status] ?? '' }}">{{ \App\Models\EmailLog::STATUS_LABELS[$log->status] ?? $log->status }}</span>
                            @if ($log->error)
                                <details class="error-details"><summary>Voir l’erreur</summary><pre>{{ $log->error }}</pre></details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Aucun email envoyé pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
@endsection
