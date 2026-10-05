@php
    $missing = fn (?string $value, string $label) => filled($value) ? e($value) : '<span class="todo">['.e($label).' à compléter]</span>';
    $rate = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',').' %';
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' €';
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',');
    // Texte saisi : echappe, **gras** autorise (ex. « Soit **8 jours** »).
    $rich = fn (string $text) => preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($text));
    $exempt = (bool) $quote->vat_exempt;
    $reservation = $quote->reservation;
    $start = $reservation ? ($reservation->start_at ?? $reservation->start_date) : null;
    $end = $reservation ? ($reservation->end_at ?? $reservation->end_date) : null;
    $validityDays = (int) $quote->issued_at->diffInDays($quote->valid_until);
    $website = preg_replace('#^https?://#', '', rtrim((string) $company['website'], '/'));
    $registration = filled($company['registration']) ? $company['registration'] : (filled($company['siret']) ? 'SIRET : '.$company['siret'] : '');
    $brand = filled($company['name']) ? $company['name'] : 'Class’Affaire';
    $font = fn (string $file) => 'url("'.resource_path('pdf/fonts/'.$file).'") format("truetype")';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->number }}</title>
    <style>
        @font-face { font-family: 'Gothic'; font-weight: normal; font-style: normal; src: {!! $font('URWGothic-Book.ttf') !!}; }
        @font-face { font-family: 'Gothic'; font-weight: bold; font-style: normal; src: {!! $font('URWGothic-Demi.ttf') !!}; }
        @font-face { font-family: 'Gothic'; font-weight: normal; font-style: italic; src: {!! $font('URWGothic-BookOblique.ttf') !!}; }
        @font-face { font-family: 'Gothic'; font-weight: bold; font-style: italic; src: {!! $font('URWGothic-DemiOblique.ttf') !!}; }

        @page { margin: 34px 44px 64px 44px; }
        body { margin: 0; font-family: 'Gothic', 'DejaVu Sans', sans-serif; color: #2b2b2b; font-size: 7.6pt; line-height: 1.3; }
        table { border-collapse: separate; border-spacing: 0; }
        .serif { font-family: 'Times', serif; }
        .todo { color: #c2410c; font-weight: bold; }

        /* En-tete : bloc noir (logo + coordonnees) et bloc gris (DEVIS) */
        .head { width: 100%; }
        .head td { vertical-align: top; }
        .head .dark { width: 53.5%; background: #000; color: #fff; padding: 8px 12px 8px 6px; height: 112px; }
        .head .light { background: #efefef; padding: 12px 10px 8px 14px; text-align: right; }
        .logo { width: 126px; }
        .tagline { padding-left: 58px; font-weight: bold; font-size: 7pt; text-transform: uppercase; text-align: right; line-height: 1.25; padding-top: 5px; }
        .tagline-rule { border-top: 1px solid #fff; width: 172px; margin: 6px 0 6px auto; }
        .coords { text-align: right; font-size: 6.4pt; line-height: 1.55; }
        .doc-title { font-family: 'Times', serif; font-size: 27pt; line-height: 1; letter-spacing: 1px; color: #111; margin-bottom: 9px; }
        .doc-meta { color: #4a4a4a; font-size: 8pt; line-height: 1.32; }

        /* Destinataire / Objet */
        .parties { width: 100%; margin-top: 22px; }
        .parties td { vertical-align: top; width: 50%; }
        .parties .left { padding-right: 16px; }
        .parties .left div.inner { border-bottom: 1px solid #555; padding-bottom: 8px; }
        .parties .right { padding-left: 0; }
        .parties .right div.inner { border-bottom: 1px solid #c8c8c8; padding-bottom: 8px; }
        .label { color: #6b6b6b; font-size: 5.8pt; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 3px; }
        .client-name { font-family: 'Times', serif; font-weight: bold; font-size: 10.5pt; text-transform: uppercase; color: #111; }
        .client-address { font-family: 'Times', serif; font-size: 8.5pt; color: #222; }
        .muted { color: #555; }
        .subject { font-family: 'Times', serif; font-size: 9pt; text-transform: uppercase; color: #111; }

        /* Detail des prestations */
        .section-title { margin-top: 24px; color: #6b6b6b; font-size: 5.8pt; text-transform: uppercase; letter-spacing: .3px; border-bottom: 1px solid #111; padding-bottom: 2px; }
        table.lines { width: 100%; margin-top: 15px; }
        table.lines th { color: #fff; font-weight: bold; font-size: 6.4pt; text-transform: uppercase; padding: 6px 10px; text-align: left; }
        table.lines thead tr { background: #0a0a0a; }
        table.lines th.num { text-align: right; }
        table.lines td { padding: 5px 10px 6px; vertical-align: top; font-size: 8pt; }
        table.lines tr.alt td { background: #f2f2f2; }
        table.lines tbody tr.last td { border-bottom: 1px solid #c8c8c8; }
        .line-title { font-weight: bold; font-size: 8.4pt; color: #111; }
        .line-detail { font-style: italic; color: #777; font-size: 6.8pt; }
        .line-detail strong { color: #555; }
        .qty { text-align: left; white-space: nowrap; }
        .num { text-align: right; white-space: nowrap; }

        /* Totaux */
        .totals { width: 41%; margin-left: 59%; margin-top: 12px; }
        .totals td { padding: 3px 10px; color: #777; font-size: 7.4pt; }
        .totals td.num { color: #2b2b2b; font-size: 8pt; }
        .totals .grand { background: #0a0a0a; }
        .totals .grand td { color: #fff; font-weight: bold; padding: 3px 10px; font-size: 6.8pt; }
        .totals .grand td.num { color: #fff; font-size: 10.5pt; }

        /* Conditions & mentions */
        .rule-thick { border-top: 1.5px solid #111; margin-top: 16px; }
        table.notes { width: 100%; margin-top: 10px; }
        table.notes td { width: 50%; vertical-align: top; text-align: justify; color: #666; font-size: 7pt; line-height: 1.25; }
        table.notes td.left { padding-right: 18px; }
        .notes-title { color: #444; font-size: 6.2pt; text-transform: uppercase; margin-bottom: 1px; }

        /* Bon pour accord */
        .agreement { page-break-inside: avoid; margin-top: 18px; }
        .agreement-title { font-weight: bold; color: #111; font-size: 6.4pt; text-transform: uppercase; border-bottom: 1.5px solid #111; padding-bottom: 2px; }
        .sign-box { width: 52.5%; margin-top: 11px; border: 1px solid #cfcfcf; padding: 8px 8px 9px; font-size: 6.4pt; color: #444; }
        .sign-space { height: 28px; border-bottom: 1px solid #bdbdbd; margin-bottom: 9px; }

        .footer { position: fixed; bottom: -40px; left: 0; right: 0; border-top: 1px solid #d4d4d4; padding-top: 7px; text-align: center; color: #8a8a8a; font-size: 6pt; }
        .footer .page:after { content: counter(page); }
        .footer u { color: #555; }
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
                <div class="doc-title">DEVIS</div>
                <div class="doc-meta">
                    N° {{ $quote->number }}<br>
                    Date d’émission : {{ $quote->issued_at->format('d/m/Y') }}<br>
                    Validité : {{ $validityDays }} jour{{ $validityDays > 1 ? 's' : '' }} — soit jusqu’au {{ $quote->valid_until->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="left">
                <div class="inner">
                    <div class="label">Destinataire</div>
                    <div class="client-name">{{ mb_strtoupper($quote->customer_name) }}</div>
                    @if ($quote->customer_address)
                        @php
                            $addressLines = preg_split('/\R/', trim($quote->customer_address));
                        @endphp
                        <div class="client-address">{{ array_shift($addressLines) }}</div>
                        @foreach ($addressLines as $addressLine)
                            @if (trim($addressLine) !== '')<div class="muted">{{ $addressLine }}</div>@endif
                        @endforeach
                    @endif
                    @if ($quote->customer_phone || $quote->customer_email)
                        <div class="muted">@if ($quote->customer_phone)Tél. : {{ $quote->customer_phone }}@endif{{ $quote->customer_phone && $quote->customer_email ? '  |  ' : '' }}{{ $quote->customer_email }}</div>
                    @endif
                </div>
            </td>
            <td class="right">
                <div class="inner">
                    <div class="label">Objet de la prestation</div>
                    <div class="subject">{{ mb_strtoupper($quote->subject ?: 'Prestation') }}</div>
                    @if ($start)
                        <div class="muted">Date : Du {{ $start->format('d/m/Y') }}@if ($end && ! $end->isSameDay($start)) au {{ $end->format('d/m/Y') }}@endif</div>
                    @endif
                    @if ($reservation && ($reservation->pickup_location || $reservation->destination))
                        <div class="muted">Lieu : {{ collect([$reservation->pickup_location, $reservation->destination])->filter()->unique()->implode(' – ') }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

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
                    // Ligne sans prix (ex. « Mise à disposition — Véhicule ») : tirets, comme une ligne de titre.
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
            <tr><td>Remise{{ $quote->discount_type === 'percent' ? ' ('.$rate($quote->discount_value).')' : '' }}</td><td class="num">− {{ $money($totals['discount_ht']) }}</td></tr>
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
    </table>

    <div class="section-title" style="margin-top:28px;border-bottom-color:#c8c8c8">Conditions &amp; mentions légales</div>
    <div class="rule-thick"></div>

    <table class="notes">
        <tr>
            <td class="left">
                <div class="notes-title">Conditions générales</div>
                {{-- Un bloc par paragraphe : la derniere ligne d'un paragraphe justifie ne doit pas etre etiree. --}}
                @foreach (array_filter(array_map('trim', preg_split('/\R/', (string) $quote->conditions))) as $paragraph)
                    <div>{{ $paragraph }}</div>
                @endforeach
                @if (filled($company['iban']))<div>IBAN : {{ $company['iban'] }}</div>@endif
            </td>
            <td>
                <div class="notes-title">Mentions légales</div>
                <div>{{ trim(($exempt && filled($vatMention ?? '') ? $vatMention.' ' : '').$legalMentions) }}</div>
            </td>
        </tr>
    </table>

    <div class="agreement">
        <div class="agreement-title">Bon pour accord</div>
        <div class="sign-box">
            Signature du client — Mention "Bon pour accord"
            <div class="sign-space"></div>
            Nom &amp; Date : ________________________________
        </div>
    </div>
</body>
</html>
