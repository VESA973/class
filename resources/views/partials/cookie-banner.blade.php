{{--
    Bandeau cookies conforme CNIL (Admin > Cookies) :
    - « Tout accepter » et « Tout refuser » ont exactement le meme style ;
    - les scripts non essentiels sont dans des <template> inertes : rien n'est charge avant consentement ;
    - le choix est garde 6 mois maximum puis redemande (et redemande si la politique change de version).
--}}
@php
    $cookieConfig = app(\App\Services\CookieSettings::class)->all();
    $cc = $cookieConfig['colors'];
    $safeScripts = fn (string $html) => str_ireplace('</template', '<\/template', $html);
@endphp
@if ($cookieConfig['enabled'])
    <div id="cookie-consent"
         data-version="{{ $cookieConfig['version'] }}"
         data-max-age="{{ \App\Services\CookieSettings::MAX_AGE_DAYS }}"
         data-endpoint="{{ route('cookies.consent') }}"
         data-token="{{ csrf_token() }}"
         style="--cc-bg: {{ $cc['background'] }}; --cc-text: {{ $cc['text'] }}; --cc-button: {{ $cc['button'] }}; --cc-button-text: {{ $cc['button_text'] }};">

        {{-- Bandeau --}}
        <section class="cc-banner" role="region" aria-labelledby="cc-title" hidden data-cc-banner>
            <div class="cc-banner-text">
                <h2 id="cc-title" class="cc-title">{{ $cookieConfig['texts']['title'] }}</h2>
                <p class="cc-message">{{ $cookieConfig['texts']['message'] }}</p>
            </div>
            <div class="cc-actions">
                <button type="button" class="cc-button" data-cc-reject>{{ $cookieConfig['texts']['reject'] }}</button>
                <button type="button" class="cc-button" data-cc-accept>{{ $cookieConfig['texts']['accept'] }}</button>
                <button type="button" class="cc-link" data-cc-customize>{{ $cookieConfig['texts']['customize'] }}</button>
            </div>
        </section>

        {{-- Preferences par categorie --}}
        <div class="cc-overlay" hidden data-cc-dialog-wrap>
            <div class="cc-dialog" role="dialog" aria-modal="true" aria-labelledby="cc-dialog-title" data-cc-dialog>
                <div class="cc-dialog-head">
                    <h2 id="cc-dialog-title" class="cc-title">{{ $cookieConfig['texts']['customize'] }}</h2>
                    <button type="button" class="cc-close" aria-label="Fermer" data-cc-close>✕</button>
                </div>
                <div class="cc-categories">
                    @foreach ($cookieConfig['categories'] as $key => $category)
                        <div class="cc-category">
                            <label class="cc-category-head">
                                <span>
                                    <strong>{{ $category['label'] }}</strong>
                                    <small>{{ $category['description'] }}</small>
                                </span>
                                @if ($key === 'necessary')
                                    <span class="cc-always">Toujours actifs</span>
                                @else
                                    <input type="checkbox" class="cc-switch" data-cc-category="{{ $key }}" aria-describedby="cc-desc-{{ $key }}">
                                @endif
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="cc-actions">
                    <button type="button" class="cc-button" data-cc-reject>{{ $cookieConfig['texts']['reject'] }}</button>
                    <button type="button" class="cc-button" data-cc-accept>{{ $cookieConfig['texts']['accept'] }}</button>
                    <button type="button" class="cc-button cc-button-outline" data-cc-save>{{ $cookieConfig['texts']['save'] }}</button>
                </div>
            </div>
        </div>

        {{-- Scripts non essentiels : inertes tant que la categorie n'est pas acceptee --}}
        @foreach (\App\Services\CookieSettings::CATEGORIES as $key)
            @if (trim($cookieConfig['categories'][$key]['scripts']) !== '')
                <template data-cc-scripts="{{ $key }}">{!! $safeScripts($cookieConfig['categories'][$key]['scripts']) !!}</template>
            @endif
            <template data-cc-cookies="{{ $key }}">{{ $cookieConfig['categories'][$key]['cookies'] }}</template>
        @endforeach
    </div>

    <style>
        #cookie-consent { font-family: inherit; }
        #cookie-consent [hidden] { display: none !important; }
        .cc-banner { position: fixed; z-index: 80; right: 16px; bottom: 16px; left: 16px; display: grid; gap: 16px; max-width: 980px; margin: 0 auto; padding: 20px; border: 1px solid rgba(255,255,255,.12); border-radius: 16px; background: var(--cc-bg); color: var(--cc-text); box-shadow: 0 20px 60px rgba(0,0,0,.5); }
        @media (min-width: 900px) { .cc-banner { grid-template-columns: 1fr auto; align-items: center; padding: 22px 24px; } }
        .cc-title { margin: 0 0 6px; font-size: 16px; font-weight: 600; }
        .cc-message { margin: 0; color: color-mix(in srgb, var(--cc-text) 75%, transparent); font-size: 14px; line-height: 1.55; }
        .cc-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .cc-button { min-height: 44px; flex: 1 1 140px; padding: 0 18px; border: 1px solid var(--cc-button); border-radius: 8px; background: var(--cc-button); color: var(--cc-button-text); cursor: pointer; font: inherit; font-size: 14px; font-weight: 600; }
        .cc-button:hover { opacity: .9; }
        .cc-button-outline { background: transparent; color: var(--cc-text); }
        .cc-link { min-height: 44px; padding: 0 8px; border: 0; background: none; color: var(--cc-text); cursor: pointer; font: inherit; font-size: 14px; text-decoration: underline; text-underline-offset: 3px; }
        #cookie-consent :focus-visible { outline: 2px solid var(--cc-text); outline-offset: 2px; }
        .cc-overlay { position: fixed; z-index: 90; inset: 0; display: grid; place-items: center; padding: 16px; background: rgba(0,0,0,.65); }
        .cc-dialog { width: min(560px, 100%); max-height: calc(100vh - 32px); overflow: auto; padding: 22px; border: 1px solid rgba(255,255,255,.12); border-radius: 16px; background: var(--cc-bg); color: var(--cc-text); }
        .cc-dialog-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .cc-close { width: 36px; height: 36px; border: 0; border-radius: 8px; background: transparent; color: var(--cc-text); cursor: pointer; font-size: 16px; }
        .cc-categories { display: grid; gap: 10px; margin: 8px 0 18px; }
        .cc-category { padding: 14px; border: 1px solid rgba(255,255,255,.12); border-radius: 12px; }
        .cc-category-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; cursor: pointer; }
        .cc-category-head small { display: block; margin-top: 4px; color: color-mix(in srgb, var(--cc-text) 70%, transparent); font-size: 13px; line-height: 1.5; }
        .cc-always { flex-shrink: 0; color: color-mix(in srgb, var(--cc-text) 70%, transparent); font-size: 12px; white-space: nowrap; }
        .cc-switch { flex-shrink: 0; width: 44px; height: 24px; margin-top: 2px; appearance: none; border-radius: 999px; background: rgba(255,255,255,.2); cursor: pointer; position: relative; transition: background .2s; }
        .cc-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 999px; background: #fff; transition: transform .2s; }
        .cc-switch:checked { background: var(--cc-button); }
        .cc-switch:checked::after { transform: translateX(20px); background: var(--cc-button-text); }
    </style>

    <script>
        (function () {
            var root = document.getElementById('cookie-consent');
            var COOKIE = 'cc_consent';
            var version = parseInt(root.dataset.version, 10);
            var maxAgeDays = parseInt(root.dataset.maxAge, 10);
            var banner = root.querySelector('[data-cc-banner]');
            var dialogWrap = root.querySelector('[data-cc-dialog-wrap]');
            var dialog = root.querySelector('[data-cc-dialog]');
            var lastFocus = null;

            function read() {
                var match = document.cookie.match(/(?:^|; )cc_consent=([^;]*)/);
                if (!match) return null;
                try {
                    var data = JSON.parse(decodeURIComponent(match[1]));
                    var fresh = Date.now() - data.d < maxAgeDays * 864e5;
                    return data.v === version && fresh ? data : null;
                } catch (e) { return null; }
            }

            function uuid() {
                if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                    var r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
            }

            function clearCookies(category) {
                var tpl = root.querySelector('[data-cc-cookies="' + category + '"]');
                var names = tpl ? tpl.innerHTML.split(/[\s,]+/).filter(Boolean) : [];
                var domains = ['', location.hostname, '.' + location.hostname.replace(/^www\./, '')];
                document.cookie.split('; ').forEach(function (pair) {
                    var name = pair.split('=')[0];
                    if (names.some(function (n) { return name === n || name.indexOf(n + '_') === 0; })) {
                        domains.forEach(function (d) {
                            document.cookie = name + '=; Max-Age=0; path=/' + (d ? '; domain=' + d : '');
                        });
                    }
                });
            }

            // Active les scripts d'une categorie acceptee (copie des <script> pour qu'ils s'executent).
            function activate(category) {
                var tpl = root.querySelector('[data-cc-scripts="' + category + '"]');
                if (!tpl || tpl.dataset.done) return;
                tpl.dataset.done = '1';
                Array.prototype.forEach.call(tpl.content.childNodes, function (node) {
                    if (node.nodeName === 'SCRIPT') {
                        var script = document.createElement('script');
                        Array.prototype.forEach.call(node.attributes, function (a) { script.setAttribute(a.name, a.value); });
                        script.text = node.textContent;
                        document.body.appendChild(script);
                    } else {
                        document.body.appendChild(node.cloneNode(true));
                    }
                });
            }

            function apply(choices) {
                ['analytics', 'marketing'].forEach(function (category) {
                    if (choices[category]) { activate(category); } else { clearCookies(category); }
                });
            }

            function save(action, choices) {
                var previous = read();
                var data = { v: version, id: (previous && previous.id) || uuid(), d: Date.now(), c: choices };
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(data)) + '; Max-Age=' + (maxAgeDays * 86400) + '; path=/; SameSite=Lax' + secure;

                // Registre des consentements (preuve), sans donnee personnelle.
                fetch(root.dataset.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': root.dataset.token },
                    body: JSON.stringify({ consent_id: data.id, action: action, analytics: !!choices.analytics, marketing: !!choices.marketing }),
                    keepalive: true
                }).catch(function () {});

                var withdrawn = previous && ['analytics', 'marketing'].some(function (c) { return previous.c[c] && !choices[c]; });
                banner.hidden = true;
                closeDialog();
                apply(choices);
                // Un consentement retire : on recharge pour decharger les scripts deja executes.
                if (withdrawn) location.reload();
            }

            function openDialog() {
                var current = read();
                root.querySelectorAll('[data-cc-category]').forEach(function (box) {
                    box.checked = !!(current && current.c[box.dataset.ccCategory]);
                });
                lastFocus = document.activeElement;
                dialogWrap.hidden = false;
                dialog.querySelector('input, button').focus();
            }

            function closeDialog() {
                if (dialogWrap.hidden) return;
                dialogWrap.hidden = true;
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }

            root.addEventListener('click', function (event) {
                if (event.target.closest('[data-cc-accept]')) save('accept_all', { analytics: true, marketing: true });
                else if (event.target.closest('[data-cc-reject]')) save('reject_all', { analytics: false, marketing: false });
                else if (event.target.closest('[data-cc-customize]')) openDialog();
                else if (event.target.closest('[data-cc-close]') || event.target === dialogWrap) closeDialog();
                else if (event.target.closest('[data-cc-save]')) {
                    var choices = {};
                    root.querySelectorAll('[data-cc-category]').forEach(function (box) { choices[box.dataset.ccCategory] = box.checked; });
                    save('custom', choices);
                }
            });

            // Clavier : Echap ferme, Tab reste dans la fenetre de preferences.
            dialog.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') { closeDialog(); return; }
                if (event.key !== 'Tab') return;
                var focusable = dialog.querySelectorAll('button, input');
                var first = focusable[0], last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            });

            // Lien permanent « Gerer mes cookies » (pied de page).
            document.querySelectorAll('[data-cookie-manage]').forEach(function (link) {
                link.addEventListener('click', function (event) { event.preventDefault(); openDialog(); });
            });

            var consent = read();
            if (consent) { apply(consent.c); } else { banner.hidden = false; }
        })();
    </script>
@endif
