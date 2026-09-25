{{-- Bouton WhatsApp flottant (Admin > Parametres > Coordonnees & WhatsApp). Simple lien : aucun cookie, aucun script tiers. --}}
@php
    $whatsappUrl = app(\App\Services\ContactSettings::class)->whatsappUrl($whatsappMessage ?? null);
@endphp
@if ($whatsappUrl)
    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="whatsapp-fab{{ ! empty($whatsappRaised) ? ' whatsapp-fab--raised' : '' }}" aria-label="Nous écrire sur WhatsApp" title="Nous écrire sur WhatsApp">
        <svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.43 9.43 0 0 1-4.8-1.32l-.35-.2-3.57.93.95-3.48-.22-.36a9.4 9.4 0 0 1-1.44-5.02c0-5.2 4.24-9.44 9.45-9.44 2.52 0 4.89.99 6.67 2.77a9.37 9.37 0 0 1 2.76 6.68c0 5.2-4.24 9.44-9.44 9.44zm8.04-17.48A11.3 11.3 0 0 0 12.04.7C5.77.7.67 5.8.67 12.07c0 2 .52 3.96 1.52 5.68L.57 23.7l6.08-1.6a11.34 11.34 0 0 0 5.39 1.37h.01c6.26 0 11.36-5.1 11.37-11.37 0-3.04-1.18-5.89-3.34-8.04z"/></svg>
    </a>
    <style>
        .whatsapp-fab { position: fixed; right: 20px; bottom: 20px; z-index: 55; display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 9999px; background: #25d366; color: #fff; box-shadow: 0 10px 30px -8px rgba(0, 0, 0, .6); transition: transform .2s ease, box-shadow .2s ease; }
        .whatsapp-fab:hover { transform: scale(1.06); box-shadow: 0 14px 34px -8px rgba(37, 211, 102, .45); }
        .whatsapp-fab:focus-visible { outline: 2px solid #fafafa; outline-offset: 3px; }
        @media (max-width: 1023px) { .whatsapp-fab--raised { bottom: 92px; } }
        @media (prefers-reduced-motion: reduce) { .whatsapp-fab { transition: none; } }
        @media print { .whatsapp-fab { display: none; } }
    </style>
@endif
