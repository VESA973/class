@extends('admin.layout')

@section('title', 'Devis')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Devis</h1>
        </div>
        <form class="inline-filter filter-bar" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="N° de devis ou client…" aria-label="Rechercher un devis">
            <select name="status" aria-label="Filtrer par statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach (\App\Models\Quote::STATUS_LABELS as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @include('admin.quotes._tabs')

    <p class="form-hint" style="margin-bottom:14px">Un devis se crée depuis une demande : ouvrez la demande dans <a href="{{ route('admin.reservations.index') }}" style="text-decoration:underline">Demandes</a> puis cliquez sur « Créer un devis ». En attente de réponse : <strong>{{ \App\Models\Quote::money($pendingTotal) }} TTC</strong>.</p>

    <div class="table-card">
        <table>
            <thead>
                <tr><th>Numéro</th><th>Client</th><th>Demande</th><th>Date</th><th>Total TTC</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($quotes as $quote)
                    <tr>
                        <td><a href="{{ route('admin.quotes.edit', $quote) }}"><strong>{{ $quote->number }}</strong></a></td>
                        <td>{{ $quote->customer_name }}<span>{{ $quote->customer_email }}</span></td>
                        <td>
                            @if ($quote->reservation)
                                <a href="{{ route('admin.reservations.show', $quote->reservation) }}">n°{{ $quote->reservation->id }}</a>
                                <span>{{ $quote->reservation->vehicle?->name }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $quote->issued_at->format('d/m/Y') }}<span>Valable jusqu’au {{ $quote->valid_until->format('d/m/Y') }}</span></td>
                        <td><strong>{{ \App\Models\Quote::money($quote->total_ttc) }}</strong></td>
                        <td><span class="tag {{ $quote->status_class }}">{{ $quote->status_label }}</span></td>
                        <td class="actions"><a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" rel="noopener">PDF</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Aucun devis pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $quotes->links() }}
@endsection
