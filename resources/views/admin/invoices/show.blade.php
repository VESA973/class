@extends('admin.layout')

@section('title', $invoice->document_label.' '.$invoice->number)

@section('content')
    @php($money = fn ($value) => \App\Models\Quote::money($value))
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $invoice->document_label }} <span class="tag {{ $invoice->status_class }}">{{ $invoice->status_label }}</span> @if ($invoice->is_overdue)<span class="tag tag-danger">En retard</span>@endif</p>
            <h1>{{ $invoice->number }}</h1>
            <p class="form-hint">Émis(e) le {{ $invoice->issued_at->format('d/m/Y') }}@unless ($invoice->is_credit_note) · échéance le {{ $invoice->due_at->format('d/m/Y') }}@endunless @if ($invoice->paid_at) · payée le {{ $invoice->paid_at->format('d/m/Y') }}@endif</p>
        </div>
        <div class="form-actions">
            @if ($invoice->quote)
                <a class="btn btn-secondary" href="{{ route('admin.quotes.edit', $invoice->quote) }}">Devis {{ $invoice->quote->number }}</a>
            @endif
            @if ($invoice->reservation)
                <a class="btn btn-secondary" href="{{ route('admin.reservations.show', $invoice->reservation) }}">Demande n°{{ $invoice->reservation->id }}</a>
            @endif
            <a class="btn btn-secondary" href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" rel="noopener"><x-icon name="file-text" style="width:18px;height:18px" /> PDF</a>
        </div>
    </div>

    <div class="editor-grid quote-grid">
        <section class="form-card">
            <h2>Client</h2>
            <p>
                <strong>{{ $invoice->customer_name }}</strong>
                @if ($invoice->customer && ! $invoice->customer->trashed()) · <a href="{{ route('admin.customers.edit', $invoice->customer) }}">fiche client</a>@endif
                <br>{{ collect([$invoice->customer_email, $invoice->customer_phone])->filter()->implode(' · ') }}
                @if ($invoice->customer_address)<br>{!! nl2br(e($invoice->customer_address)) !!}@endif
            </p>

            <h2>{{ $invoice->subject ?: 'Prestations' }}</h2>
            <div class="table-card">
                <table>
                    <thead><tr><th>Désignation</th><th>Qté</th><th>P.U. HT</th><th>Total HT</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->lines as $line)
                            <tr>
                                <td>{!! nl2br(e($line->description)) !!}</td>
                                <td>{{ rtrim(rtrim((string) $line->quantity, '0'), '.') }}</td>
                                <td>{{ $money($line->unit_price_ht) }}</td>
                                <td>{{ $money($line->total_ht) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <dl class="totals">
                <dt>Sous-total HT</dt><dd>{{ $money($invoice->subtotal_ht) }}</dd>
                @if ((float) $invoice->discount_ht > 0)<dt>Remise</dt><dd>− {{ $money($invoice->discount_ht) }}</dd>@endif
                @unless ($invoice->vat_exempt)<dt>TVA</dt><dd>{{ $money($invoice->total_vat) }}</dd>@endunless
                <dt class="grand">Total{{ $invoice->vat_exempt ? '' : ' TTC' }}</dt><dd class="grand">{{ $money($invoice->total_ttc) }}</dd>
            </dl>
        </section>

        <div class="grid-stack">
            @if ($invoice->is_credit_note)
                <section class="form-card">
                    <h2>Avoir</h2>
                    <p class="form-hint">Cet avoir annule la facture @if ($invoice->creditedInvoice)<a href="{{ route('admin.invoices.show', $invoice->creditedInvoice) }}"><strong>{{ $invoice->creditedInvoice->number }}</strong></a>@endif. Les sommes déjà encaissées sont à rembourser au client.</p>
                </section>
            @else
                <section class="form-card" aria-labelledby="payment-title">
                    <h2 id="payment-title">Paiements</h2>
                    @if ($invoice->payments->isNotEmpty())
                        <ul class="plain-list">
                            @foreach ($invoice->payments as $payment)
                                <li>
                                    {{ $payment->paid_at->format('d/m/Y') }} · <strong>{{ $money($payment->amount) }}</strong>
                                    @if ($payment->method) · {{ \App\Models\InvoicePayment::METHODS[$payment->method] ?? $payment->method }}@endif
                                    @if ($payment->note) · {{ $payment->note }}@endif
                                    @unless ($invoice->status === 'cancelled')
                                        <form method="POST" action="{{ route('admin.invoices.payments.destroy', [$invoice, $payment]) }}" style="display:inline" onsubmit="return confirm('Supprimer ce paiement ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="link-button">Supprimer</button>
                                        </form>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="form-hint">Réglé : <strong>{{ $money($invoice->amount_paid) }}</strong> · reste à régler : <strong>{{ $money($invoice->balance) }}</strong></p>

                    @if ($invoice->status === 'issued')
                        @error('payment')<div class="flash flash-error">{{ $message }}</div>@enderror
                        <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}">
                            @csrf
                            <div class="form-grid">
                                <label>Montant (€)<input type="number" step="0.01" min="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ old('amount', $invoice->balance) }}" required></label>
                                <label>Date<input type="date" name="paid_at" value="{{ old('paid_at', now(config('app.local_timezone'))->toDateString()) }}" required></label>
                                <label>Mode
                                    <select name="method">
                                        <option value="">—</option>
                                        @foreach (\App\Models\InvoicePayment::METHODS as $key => $label)
                                            <option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Note<input name="note" maxlength="255" value="{{ old('note') }}" placeholder="ex. Acompte 30 %"></label>
                            </div>
                            <div class="form-actions" style="justify-content:flex-start"><button class="btn" type="submit">Enregistrer le paiement</button></div>
                        </form>
                    @endif
                </section>

                <section class="form-card" aria-labelledby="credit-title">
                    <h2 id="credit-title">Annulation</h2>
                    @error('invoice')<div class="flash flash-error">{{ $message }}</div>@enderror
                    @if ($invoice->creditNote)
                        <p class="form-hint">Annulée par l’avoir <a href="{{ route('admin.invoices.show', $invoice->creditNote) }}"><strong>{{ $invoice->creditNote->number }}</strong></a>.</p>
                    @else
                        <p class="form-hint">Une facture émise ne se supprime pas : l’avoir l’annule entièrement. Vous pourrez ensuite refacturer le devis ou créer une nouvelle facture.</p>
                        <form method="POST" action="{{ route('admin.invoices.credit-note', $invoice) }}" onsubmit="return confirm('Émettre un avoir qui annule la facture {{ $invoice->number }} ?')">
                            @csrf
                            <div class="form-actions" style="justify-content:flex-start"><button class="btn btn-danger" type="submit">Émettre un avoir</button></div>
                        </form>
                    @endif
                </section>
            @endif

            <section class="form-card" aria-labelledby="send-title">
                <h2 id="send-title">Envoyer au client</h2>
                <p class="form-hint">Le PDF est joint à l’email « Envoi de facture » (modifiable dans Emails › Modèles).</p>
                @error('send')<div class="flash flash-error">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}" onsubmit="return confirm('Envoyer {{ $invoice->number }} par email ?')">
                    @csrf
                    <label>Destinataire<input type="email" name="to" value="{{ old('to', $invoice->customer_email) }}" required></label>
                    <div class="form-actions"><button class="btn" type="submit">{{ $invoice->sent_at ? 'Renvoyer' : 'Envoyer' }} {{ $invoice->is_credit_note ? 'l’avoir' : 'la facture' }}</button></div>
                </form>
                @if ($invoice->sent_at)
                    <p class="form-hint">Dernier envoi : {{ $invoice->sent_at->timezone(config('app.local_timezone'))->format('d/m/Y à H:i') }}</p>
                @endif
            </section>

            @if ($invoice->notes)
                <section class="form-card"><h2>Notes internes</h2><p>{!! nl2br(e($invoice->notes)) !!}</p></section>
            @endif
        </div>
    </div>
@endsection
