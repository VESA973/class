@extends('admin.layout')

@section('title', 'Factures')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Demandes & Devis</p>
            <h1>Factures</h1>
        </div>
        <div class="form-actions">
        <a class="btn" href="{{ route('admin.invoices.create') }}">+ Nouvelle facture</a>
        <form class="inline-filter filter-bar" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="N° de facture ou client…" aria-label="Rechercher une facture">
            <select name="status" aria-label="Filtrer par statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach (\App\Models\Invoice::STATUS_LABELS as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        </div>
    </div>

    <p class="form-hint" style="margin-bottom:14px">Créez une facture libre avec « Nouvelle facture », ou depuis un <a href="{{ route('admin.quotes.index') }}" style="text-decoration:underline">devis</a> (bouton « Créer la facture »). Reste à encaisser : <strong>{{ \App\Models\Quote::money($unpaidTotal) }}</strong>.</p>

    <div class="table-card">
        <table>
            <thead>
                <tr><th>Numéro</th><th>Client</th><th>Devis</th><th>Date</th><th>Total</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('admin.invoices.show', $invoice) }}"><strong>{{ $invoice->number }}</strong></a></td>
                        <td>{{ $invoice->customer_name }}<span>{{ $invoice->customer_email }}</span></td>
                        <td>@if ($invoice->quote)<a href="{{ route('admin.quotes.edit', $invoice->quote) }}">{{ $invoice->quote->number }}</a>@else — @endif</td>
                        <td>{{ $invoice->issued_at->format('d/m/Y') }}@unless ($invoice->is_credit_note)<span>Échéance {{ $invoice->due_at->format('d/m/Y') }}</span>@endunless</td>
                        <td><strong>{{ $invoice->is_credit_note ? '− ' : '' }}{{ \App\Models\Quote::money($invoice->total_ttc) }}</strong>@if ($invoice->status === 'issued' && $invoice->amount_paid > 0)<span>Reste {{ \App\Models\Quote::money($invoice->balance) }}</span>@endif</td>
                        <td>
                            <span class="tag {{ $invoice->status_class }}">{{ $invoice->status_label }}</span>
                            @if ($invoice->is_overdue)<span class="tag tag-danger">En retard</span>@endif
                        </td>
                        <td class="actions"><a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" rel="noopener">PDF</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">Aucune facture pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $invoices->links() }}
@endsection
