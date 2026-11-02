{{-- Styles communs aux PDF devis et facture (modele Class'Affaire). --}}
@php($font = fn (string $file) => 'url("'.resource_path('pdf/fonts/'.$file).'") format("truetype")')
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
