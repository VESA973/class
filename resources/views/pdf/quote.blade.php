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
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $quote->number }}</title>
    @include('pdf._styles')
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
