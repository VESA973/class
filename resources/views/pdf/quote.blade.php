@php
    $missing = fn (?string $value, string $label) => filled($value) ? e($value) : '<span class="todo">['.e($label).' à compléter]</span>';
    $rate = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',').' %';
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' €';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->number }}</title>
    <style>
        @page { margin: 0; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #18181b; font-size: 10pt; line-height: 1.45; }
        .band { background: #0a0a0a; color: #fafafa; padding: 26px 40px; }
        .band table { width: 100%; }
        .brand { font-size: 17pt; font-weight: bold; letter-spacing: 3px; }
        .brand-sub { color: #a1a1aa; font-size: 8.5pt; letter-spacing: 1px; }
        .doc-title { text-align: right; font-size: 20pt; font-weight: bold; letter-spacing: 2px; }
        .doc-number { text-align: right; color: #d4d4d8; font-size: 10pt; }
        .page { padding: 26px 40px 30px; }
        .parties { width: 100%; margin-bottom: 20px; }
        .parties td { width: 50%; vertical-align: top; }
        .box { border: 1px solid #e4e4e7; border-radius: 6px; padding: 12px 14px; }
        .label { color: #71717a; font-size: 7.5pt; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
        .name { font-weight: bold; font-size: 11pt; }
        .meta { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .meta td { padding: 6px 10px; background: #f4f4f5; border-right: 2px solid #fff; }
        .meta strong { display: block; font-size: 9.5pt; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th { background: #18181b; color: #fafafa; font-size: 8pt; text-transform: uppercase; letter-spacing: .5px; padding: 8px; text-align: left; }
        table.lines td { padding: 8px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 46%; margin-left: 54%; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 5px 8px; }
        .totals .grand td { background: #0a0a0a; color: #fafafa; font-weight: bold; font-size: 11.5pt; padding: 9px 8px; }
        .section { margin-top: 20px; }
        .section h3 { font-size: 9pt; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 6px; }
        .conditions { color: #3f3f46; font-size: 9pt; white-space: pre-line; }
        .signature { width: 100%; margin-top: 22px; }
        .signature td { width: 50%; vertical-align: top; }
        .sign-box { height: 70px; border: 1px dashed #a1a1aa; border-radius: 6px; margin-top: 6px; }
        .todo { color: #c2410c; font-weight: bold; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 40px 14px; border-top: 1px solid #e4e4e7; color: #71717a; font-size: 7.5pt; text-align: center; }
    </style>
</head>
<body>
    <div class="band">
        <table>
            <tr>
                <td>
                    <div class="brand">CLASS’AFFAIRE</div>
                    <div class="brand-sub">LOCATION DE VÉHICULES DE PRESTIGE AVEC OU SANS CHAUFFEUR</div>
                </td>
                <td>
                    <div class="doc-title">DEVIS</div>
                    <div class="doc-number">N° {{ $quote->number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="page">
        <table class="parties">
            <tr>
                <td style="padding-right:10px">
                    <div class="box">
                        <div class="label">Émetteur</div>
                        <div class="name">{!! $missing($company['name'], 'Raison sociale') !!}</div>
                        @if (filled($company['legal_form']))<div>{{ $company['legal_form'] }}</div>@endif
                        <div>{!! nl2br($missing($company['address'], 'Adresse du siège')) !!}</div>
                        <div>SIRET : {!! $missing($company['siret'], 'SIRET') !!}</div>
                        <div>TVA : {!! $missing($company['vat_number'], 'N° de TVA intracommunautaire') !!}</div>
                        <div>{{ $company['phone'] }} · {{ $company['email'] }}</div>
                    </div>
                </td>
                <td style="padding-left:10px">
                    <div class="box">
                        <div class="label">Client</div>
                        <div class="name">{{ $quote->customer_name }}</div>
                        @if ($quote->customer_address)<div>{!! nl2br(e($quote->customer_address)) !!}</div>@endif
                        @if ($quote->customer_email)<div>{{ $quote->customer_email }}</div>@endif
                        @if ($quote->customer_phone)<div>{{ $quote->customer_phone }}</div>@endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td><span class="label">Date</span><strong>{{ $quote->issued_at->format('d/m/Y') }}</strong></td>
                <td><span class="label">Valable jusqu’au</span><strong>{{ $quote->valid_until->format('d/m/Y') }}</strong></td>
                @if ($quote->reservation)
                    <td><span class="label">Demande</span><strong>n°{{ $quote->reservation->id }}</strong></td>
                @endif
                @if ($quote->subject)
                    <td><span class="label">Objet</span><strong>{{ $quote->subject }}</strong></td>
                @endif
            </tr>
        </table>

        @if ($quote->reservation)
            <p style="margin:0 0 14px;color:#3f3f46;font-size:9pt">
                <strong>Trajet :</strong> {{ $quote->reservation->pickup_location }}{{ $quote->reservation->destination ? ' → '.$quote->reservation->destination : '' }}
                · <strong>Départ :</strong> {{ ($quote->reservation->start_at ?? $quote->reservation->start_date)?->format('d/m/Y H:i') }}
                · <strong>Retour :</strong> {{ ($quote->reservation->end_at ?? $quote->reservation->end_date)?->format('d/m/Y H:i') }}
                @if ($quote->reservation->passengers) · <strong>Passagers :</strong> {{ $quote->reservation->passengers }} @endif
            </p>
        @endif

        <table class="lines">
            <thead>
                <tr><th style="width:48%">Désignation</th><th class="num">Qté</th><th class="num">Prix unit. HT</th><th class="num">TVA</th><th class="num">Total HT</th></tr>
            </thead>
            <tbody>
                @foreach ($totals['lines'] as $line)
                    <tr>
                        <td>{!! nl2br(e($line['description'])) !!}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($line['quantity'], 2, ',', ' '), '0'), ',') }}</td>
                        <td class="num">{{ $money($line['unit_price_ht']) }}</td>
                        <td class="num">{{ $rate($line['vat_rate']) }}</td>
                        <td class="num">{{ $money($line['total_ht']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Sous-total HT</td><td class="num">{{ $money($totals['subtotal_ht']) }}</td></tr>
            @if ($totals['discount_ht'] > 0)
                <tr><td>Remise{{ $quote->discount_type === 'percent' ? ' ('.$rate($quote->discount_value).')' : '' }}</td><td class="num">− {{ $money($totals['discount_ht']) }}</td></tr>
                <tr><td>Total HT après remise</td><td class="num">{{ $money($totals['total_ht']) }}</td></tr>
            @endif
            @foreach ($totals['vat_breakdown'] as $vatRate => $vatAmount)
                <tr><td>TVA {{ $rate($vatRate) }}</td><td class="num">{{ $money($vatAmount) }}</td></tr>
            @endforeach
            <tr class="grand"><td>Total TTC</td><td class="num">{{ $money($totals['total_ttc']) }}</td></tr>
        </table>

        @if ($quote->conditions)
            <div class="section">
                <h3>Conditions</h3>
                <div class="conditions">{{ $quote->conditions }}</div>
            </div>
        @endif

        @if (filled($company['iban']))
            <div class="section"><h3>Règlement</h3><div class="conditions">IBAN : {{ $company['iban'] }}</div></div>
        @endif

        <table class="signature">
            <tr>
                <td></td>
                <td>
                    <div class="label">Bon pour accord — date et signature du client</div>
                    <div class="sign-box"></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {!! $missing($company['name'], 'Raison sociale') !!} · SIRET {!! $missing($company['siret'], 'SIRET') !!} · TVA {!! $missing($company['vat_number'], 'N° TVA') !!} · {{ $company['email'] }} · {{ $company['phone'] }}
    </div>
</body>
</html>
