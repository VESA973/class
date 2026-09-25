/**
 * Genere resources/views/components/icon.blade.php a partir des icones lucide-react
 * installees, pour utiliser les memes icones dans les pages Blade (rendu serveur).
 *
 * Usage : node scripts/blade-icons.mjs   (ajouter les noms voulus dans ICONS)
 */
import { writeFileSync } from 'node:fs';

const ICONS = [
    'arrow-right', 'calendar-check', 'calendar-days', 'car-front', 'check', 'circle-check', 'clock', 'fuel', 'gauge',
    'headset', 'mail', 'map-pin', 'menu', 'moon', 'phone', 'quote', 'search', 'send', 'settings-2', 'shield-check',
    'sparkles', 'star', 'sun', 'truck', 'user-round', 'x', 'users', 'rotate-3d', 'play', 'image', 'arrow-left', 'box',
    'layout-dashboard', 'clipboard-list', 'scale', 'cookie', 'settings', 'external-link', 'log-out', 'wrench', 'file-text', 'hourglass', 'calendar-clock', 'bell',
];

const escape = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
const entries = [];

for (const name of ICONS) {
    const { __iconData } = await import(`../node_modules/lucide-react/dist/esm/icons/${name}.mjs`);
    const markup = __iconData.node
        .map(([tag, attrs]) => {
            const attributes = Object.entries(attrs)
                .filter(([key]) => key !== 'key')
                .map(([key, value]) => `${key}="${escape(value)}"`)
                .join(' ');

            return `<${tag} ${attributes}/>`;
        })
        .join('');
    entries.push(`    '${name}' => '${markup.replace(/'/g, "\\'")}',`);
}

writeFileSync(
    new URL('../resources/views/components/icon.blade.php', import.meta.url),
    `{{-- Genere par scripts/blade-icons.mjs a partir de lucide-react (licence ISC). Ne pas modifier a la main. --}}
@props(['name'])
@php
$icons = [
${entries.join('\n')}
];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" {{ $attributes->merge(['class' => 'size-5 shrink-0']) }}>{!! $icons[$name] ?? '' !!}</svg>
`,
);

console.log(`${ICONS.length} icones ecrites.`);
