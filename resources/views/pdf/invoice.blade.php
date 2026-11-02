@php
    $missing = fn (?string $value, string $label) => filled($value) ? e($value) : '<span class="todo">['.e($label).' à compléter]</span>';
    $rate = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',').' %';
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' €';
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    $rich = fn (string $text) => preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($text));
    $invoice = $quote; // la facture est passee sous le nom $quote par QuoteService::pdf()
    $exempt = (bool) $invoice->vat_exempt;
    $isCreditNote = $invoice->is_credit_note;
    $reservation = $invoice->reservation;
    $start = $reservation ? ($reservation->start_at ?? $reservation->start_date) : null;
    $end = $reservation ? ($reservation->end_at ?? $reservation->end_date) : null;
    $website = preg_replace('#^https?://#', '', rtrim((string) $company['website'], '/'));
    $registration = filled($company['registration']) ? $company['registration'] : (filled($company['siret']) ? 'SIRET : '.$company['siret'] : '');
    $brand = filled($company['name']) ? $company['name'] : 'Class’Affaire';
    $ttc = $exempt ? '' : ' TTC';
    $payments = $isCreditNote ? collect() : $invoice->payments;
    $paid = $invoice->amount_paid;
    $dueDays = (int) $invoice->issued_at->diffInDays($invoice->due_at);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->document_label }} {{ $invoice->number }}</title>
    @include('pdf._styles')
    <style>
        .client-name { text-transform: none; }
        table.lines td.num { font-weight: bold; color: #111; }
        .paid-band { margin-top: 18px; background: #f2f2f2; border-left: 2.5px solid #111; padding: 6px 10px; font-size: 7.2pt; color: #555; }
        .paid-band strong { color: #111; text-transform: uppercase; }
        .totals .net td { background: #efefef; color: #555; }
        .totals .net td.num { color: #111; font-size: 9pt; }
        table.terms { width: 100%; margin-top: 10px; }
        table.terms td { width: 50%; vertical-align: top; color: #444; font-size: 7.6pt; line-height: 1.45; }
        table.terms td.left { padding-right: 18px; }
        .legal-line { margin-top: 16px; font-size: 6.8pt; color: #555; }
        .legal-line em { color: #666; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $brand }}@if ($website) &nbsp;-&nbsp; <u>{{ $website }}</u>@endif &nbsp;-&nbsp; Page <span class="page"></span> / {{ $pageCount ?? 1 }}
    </div>

    <table class="head">
        <tr>
            <td class="dark">
                <table style="width:100%">
                    <tr>
                        <td style="width:126px;vertical-align:middle"><img class="logo" src="{{ $logo }}" alt=""></td>
                        <td style="vertical-align:top">
                            @if (filled($company['tagline']))<div class="tagline">{{ mb_strtoupper($company['tagline']) }}</div>@endif
                            <div class="tagline-rule"></div>
                            <div class="coords">
                                {!! $missing(preg_replace('/\s*\R\s*/', ' — ', trim((string) $company['address'])), 'Adresse') !!}<br>
                                @if (filled($company['email'])){{ $company['email'] }}<br>@endif
                                @if (filled($company['phone'])){{ $company['phone'] }}<br>@endif
                                {!! $missing($registration, 'SIREN / SIRET') !!}
                                @unless ($exempt || blank($company['vat_number']))<br>TVA : {{ $company['vat_number'] }}@endunless
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="light">
                <div class="doc-title">{{ mb_strtoupper($invoice->document_label) }}</div>
                <div class="doc-meta">
                    N° {{ $invoice->number }}<br>
                    Date de facturation : {{ $invoice->issued_at->format('d/m/Y') }}<br>
                    @if ($isCreditNote)
                        Annule la facture : {{ $invoice->creditedInvoice?->number }}
                    @else
                        Date d’échéance : {{ $invoice->due_at->format('d/m/Y') }}
                    @endif
                    @if ($invoice->quote)<br>Réf. devis : {{ $invoice->quote->number }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="left">
                <div class="inner">
                    <div class="label">Facturé à</div>
                    <div class="client-name">{{ $invoice->customer_name }}</div>
                    @if ($invoice->customer_address)
                        @foreach (preg_split('/\R/', trim($invoice->customer_address)) as $addressLine)
                            @if (trim($addressLine) !== '')<div class="muted">{{ $addressLine }}</div>@endif
                        @endforeach
                    @endif
                    @if ($invoice->customer_phone || $invoice->customer_email)
                        <div class="muted">@if ($invoice->customer_phone)Tél. : {{ $invoice->customer_phone }}@endif{{ $invoice->customer_phone && $invoice->customer_email ? '  |  ' : '' }}{{ $invoice->customer_email }}</div>
                    @endif
                </div>
            </td>
            <td class="right">
                <div class="inner">
                    <div class="label">Détails de la prestation</div>
                    @if ($invoice->subject || $reservation?->prestation_type)
                        <div class="muted" style="font-style:italic">{{ $invoice->subject ?: $reservation->prestation_type }}</div>
                    @endif
                    @if ($start)
                        <div class="muted">Date de la prestation : {{ $start->format('d/m/Y') }}@if ($end && ! $end->isSameDay($start)) au {{ $end->format('d/m/Y') }}@endif</div>
                    @endif
                    @if ($reservation?->pickup_location)<div class="muted">Lieu de départ : {{ $reservation->pickup_location }}</div>@endif
                    @if ($reservation?->destination)<div class="muted">Lieu d’arrivée : {{ $reservation->destination }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    @if ($paid > 0)
        <div class="paid-band">
            <strong>{{ $invoice->balance > 0 ? 'Acompte perçu' : 'Paiement reçu' }} :</strong> {{ $money($paid) }}{{ $ttc }}
            @if ((float) $invoice->total_ttc > 0) ({{ $rate(round($paid / (float) $invoice->total_ttc * 100, 1)) }})@endif
            — réglé le {{ $payments->pluck('paid_at')->map->format('d/m/Y')->unique()->implode(', ') }}
            @if ($methods = $payments->pluck('method')->filter()->unique()->map(fn ($method) => \App\Models\InvoicePayment::METHODS[$method] ?? $method)->implode(' / '))par {{ $methods }}@endif
            &nbsp;·&nbsp; <strong>Reste à payer : {{ $money($invoice->balance) }}{{ $ttc }}</strong>
        </div>
    @endif

    <div class="section-title">Détail des prestations</div>

    <table class="lines">
        <thead>
            <tr>
                <th style="width:54%">Désignation</th>
                <th class="qty" style="width:10%">Qté</th>
                <th class="num" style="width:18%">P.U. HT</th>
                <th class="num" style="width:18%">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($totals['lines'] as $line)
                @php
                    $descriptionLines = preg_split('/\R/', $line['description']);
                    $title = array_shift($descriptionLines);
                    $heading = (float) $line['unit_price_ht'] == 0.0;
                @endphp
                <tr class="{{ $loop->odd ? '' : 'alt' }} {{ $loop->last ? 'last' : '' }}">
                    <td>
                        <div class="line-title">{!! $rich($title) !!}</div>
                        @if (count($descriptionLines))<div class="line-detail">{!! implode('<br>', array_map($rich, $descriptionLines)) !!}</div>@endif
                    </td>
                    <td class="qty">{{ $heading ? '—' : $qty($line['quantity']) }}</td>
                    <td class="num">{{ $heading ? '—' : $money($line['unit_price_ht']) }}</td>
                    <td class="num">{{ $heading ? '—' : $money($line['total_ht']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sous-total HT</td><td class="num">{{ $money($totals['subtotal_ht']) }}</td></tr>
        @if ($totals['discount_ht'] > 0)
            <tr><td>Remise{{ $invoice->discount_type === 'percent' ? ' ('.$rate($invoice->discount_value).')' : '' }}</td><td class="num">− {{ $money($totals['discount_ht']) }}</td></tr>
            <tr><td>Total HT après remise</td><td class="num">{{ $money($totals['total_ht']) }}</td></tr>
        @endif
        @if ($exempt || empty($totals['vat_breakdown']))
            <tr><td>TVA (0 %)</td><td class="num"></td></tr>
        @else
            @foreach ($totals['vat_breakdown'] as $vatRate => $vatAmount)
                <tr><td>TVA ({{ $rate($vatRate) }})</td><td class="num">{{ $money($vatAmount) }}</td></tr>
            @endforeach
        @endif
        <tr class="grand"><td>TOTAL TTC</td><td class="num">{{ $money($totals['total_ttc']) }}</td></tr>
        @if ($paid > 0)
            <tr><td>Acompte versé</td><td class="num">− {{ $money($paid) }}</td></tr>
            <tr class="net"><td>NET À PAYER</td><td class="num">{{ $money($invoice->balance) }}</td></tr>
        @endif
    </table>

    @if ($isCreditNote)
        <div class="section-title" style="margin-top:28px">Avoir</div>
        <div class="rule-thick"></div>
        <table class="terms"><tr><td class="left" style="width:100%">Avoir annulant la facture n° {{ $invoice->creditedInvoice?->number }}. Les sommes déjà réglées vous seront remboursées.</td></tr></table>
    @else
        <div class="section-title" style="margin-top:28px">Modalités de règlement</div>
        <div class="rule-thick"></div>
        <table class="terms">
            <tr>
                <td class="left">
                    <div class="label">Modalités de règlement</div>
                    Mode : {{ $company['payment_methods'] ?: 'Virement bancaire / CB' }}<br>
                    Échéance : {{ $dueDays > 0 ? $dueDays.' jours date de facture' : 'à réception de facture' }} (au plus tard le {{ $invoice->due_at->format('d/m/Y') }})<br>
                    Pénalités de retard : 3× le taux légal en vigueur<br>
                    Indemnité forfaitaire de recouvrement : 40 €<br>
                    Escompte pour paiement anticipé : néant
                </td>
                <td>
                    <div class="label">Coordonnées bancaires (virement)</div>
                    Banque : {!! $missing($company['bank'], 'Banque') !!}<br>
                    IBAN : {!! $missing($company['iban'], 'IBAN') !!}<br>
                    BIC : {!! $missing($company['bic'], 'BIC') !!}<br>
                    Libellé : {{ $invoice->number }} + {{ $invoice->customer_name }}
                </td>
            </tr>
        </table>
    @endif

    @if ($exempt && filled($vatMention ?? ''))
        <div class="legal-line"><strong>Mention légale :</strong> <em>{{ $vatMention }}</em></div>
    @endif
</body>
</html>
