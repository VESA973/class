@extends('admin.layout')

@section('title', 'Devis '.$quote->number)

@php
    $lines = old('lines', $quote->lines->map(fn ($line) => [
        'description' => $line->description,
        'quantity' => rtrim(rtrim((string) $line->quantity, '0'), '.'),
        'unit_price_ht' => $line->unit_price_ht,
        'vat_rate' => rtrim(rtrim((string) $line->vat_rate, '0'), '.'),
    ])->all());
    $statusClass = $quote->status_class;
    $missingCompany = collect(['name' => 'raison sociale', 'siret' => 'SIRET', 'address' => 'adresse'])->filter(fn ($label, $key) => blank($company[$key] ?? null));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Devis <span class="tag {{ $statusClass }}">{{ $quote->status_label }}</span></p>
            <h1>{{ $quote->number }}</h1>
        </div>
        <div class="form-actions">
            @if ($quote->reservation)
                <a class="btn btn-secondary" href="{{ route('admin.reservations.show', $quote->reservation) }}">Demande n°{{ $quote->reservation->id }}</a>
            @endif
            <a class="btn btn-secondary" href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" rel="noopener"><x-icon name="file-text" style="width:18px;height:18px" /> Aperçu PDF</a>
        </div>
    </div>

    @if ($missingCompany->isNotEmpty())
        <div class="maintenance-banner" role="note">
            <span>Informations de l’entreprise à compléter pour le PDF : {{ $missingCompany->implode(', ') }}.</span>
            <a href="{{ route('admin.quotes.settings') }}">Compléter</a>
        </div>
    @endif

    <div class="editor-grid quote-grid">
        <form class="form-card" method="POST" action="{{ route('admin.quotes.update', $quote) }}" data-quote-form>
            @csrf
            @method('PUT')

            <h2>Client</h2>
            <div class="form-grid">
                <label>Nom<input name="customer_name" value="{{ old('customer_name', $quote->customer_name) }}" required maxlength="255"></label>
                <label>Email<input type="email" name="customer_email" value="{{ old('customer_email', $quote->customer_email) }}" maxlength="255"></label>
                <label>Téléphone<input name="customer_phone" value="{{ old('customer_phone', $quote->customer_phone) }}" maxlength="40"></label>
                <label>Adresse (facultative)<input name="customer_address" value="{{ old('customer_address', $quote->customer_address) }}" maxlength="500"></label>
            </div>

            <h2>Devis</h2>
            <div class="form-grid">
                <label>Objet<input name="subject" value="{{ old('subject', $quote->subject) }}" maxlength="255"></label>
                <div class="form-grid">
                    <label>Date<input type="date" name="issued_at" value="{{ old('issued_at', $quote->issued_at->format('Y-m-d')) }}" required></label>
                    <label>Valable jusqu’au<input type="date" name="valid_until" value="{{ old('valid_until', $quote->valid_until->format('Y-m-d')) }}" required></label>
                </div>
            </div>

            <div class="table-card quote-lines">
                <table>
                    <thead>
                        <tr><th style="width:44%">Désignation</th><th>Qté</th><th>Prix unit. HT</th><th>TVA</th><th>Total HT</th><th><span class="sr-only">Supprimer</span></th></tr>
                    </thead>
                    <tbody data-lines>
                        @foreach ($lines as $index => $line)
                            @include('admin.quotes._line', ['index' => $index, 'line' => $line])
                        @endforeach
                    </tbody>
                </table>
            </div>
            <template data-line-template>
                @include('admin.quotes._line', ['index' => '__INDEX__', 'line' => ['description' => '', 'quantity' => 1, 'unit_price_ht' => 0, 'vat_rate' => '20']])
            </template>
            <div><button type="button" class="btn btn-secondary" data-add-line>+ Ajouter une ligne</button></div>

            <div class="quote-bottom">
                <div class="form-grid" style="align-content:start">
                    <label>Remise
                        <select name="discount_type" data-discount-type>
                            <option value="none" @selected(old('discount_type', $quote->discount_type) === 'none')>Aucune</option>
                            <option value="percent" @selected(old('discount_type', $quote->discount_type) === 'percent')>Pourcentage (%)</option>
                            <option value="amount" @selected(old('discount_type', $quote->discount_type) === 'amount')>Montant HT (€)</option>
                        </select>
                    </label>
                    <label>Valeur<input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', (float) $quote->discount_value) }}" data-discount-value></label>
                </div>
                <dl class="totals" aria-live="polite">
                    <dt>Sous-total HT</dt><dd data-total="subtotal">—</dd>
                    <dt>Remise</dt><dd data-total="discount">—</dd>
                    <dt>Total HT</dt><dd data-total="ht">—</dd>
                    <dt>TVA</dt><dd data-total="vat">—</dd>
                    <dt class="grand">Total TTC</dt><dd class="grand" data-total="ttc">—</dd>
                </dl>
            </div>

            <label>Conditions (affichées sur le PDF)<textarea name="conditions" rows="4" maxlength="5000">{{ old('conditions', $quote->conditions) }}</textarea></label>
            <label>Notes internes (non affichées au client)<textarea name="notes" rows="2" maxlength="5000">{{ old('notes', $quote->notes) }}</textarea></label>

            <div class="form-actions">
                @if ($quote->status === 'draft')
                    <button class="btn btn-danger" type="submit" form="delete-quote">Supprimer le brouillon</button>
                @endif
                <button class="btn" type="submit">Enregistrer</button>
            </div>
        </form>

        <div class="grid-stack">
            <section class="form-card" aria-labelledby="send-title">
                <h2 id="send-title">Envoyer au client</h2>
                <p class="form-hint">Le PDF est joint à l’email « Envoi de devis » (modifiable dans Emails › Modèles). Enregistrez vos modifications avant l’envoi.</p>
                @error('send')<div class="flash flash-error">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('admin.quotes.send', $quote) }}" class="form-card" style="padding:0;border:0;background:none" onsubmit="return confirm('Envoyer le devis {{ $quote->number }} par email ?')">
                    @csrf
                    <label>Destinataire<input type="email" name="to" value="{{ old('to', $quote->customer_email) }}" required></label>
                    <div class="form-actions"><button class="btn" type="submit">{{ $quote->sent_at ? 'Renvoyer le devis' : 'Envoyer le devis' }}</button></div>
                </form>
                @if ($quote->sent_at)
                    <p class="form-hint">Dernier envoi : {{ $quote->sent_at->timezone(config('app.local_timezone'))->format('d/m/Y à H:i') }}</p>
                @endif
            </section>

            <section class="form-card" aria-labelledby="answer-title">
                <h2 id="answer-title">Réponse du client</h2>
                <div class="form-actions" style="justify-content:flex-start">
                    @foreach (['accepted' => 'Marquer accepté', 'refused' => 'Marquer refusé'] as $status => $label)
                        <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button class="btn {{ $status === 'accepted' ? 'btn-secondary' : 'btn-danger' }}" type="submit" @disabled($quote->status === $status)>{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
                <p class="form-hint">Le suivi de la demande est mis à jour automatiquement.</p>
            </section>

            @if ($events->isNotEmpty())
                <section class="form-card" aria-labelledby="history-title">
                    <h2 id="history-title">Historique de la demande</h2>
                    @include('admin.reservations._timeline', ['events' => $events])
                </section>
            @endif
        </div>
    </div>

    <form id="delete-quote" method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return confirm('Supprimer définitivement ce brouillon ?')">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        // Lignes du devis : ajout / suppression et totaux en direct (le serveur recalcule a l'enregistrement).
        (function () {
            var form = document.querySelector('[data-quote-form]');
            var body = form.querySelector('[data-lines]');
            var template = document.querySelector('[data-line-template]');
            var next = body.children.length;
            var money = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
            var cents = function (value) { return Math.round((parseFloat(String(value).replace(',', '.')) || 0) * 100); };

            function recalc() {
                var subtotal = 0, byRate = {};
                body.querySelectorAll('tr').forEach(function (row) {
                    var qty = parseFloat(row.querySelector('[data-qty]').value.replace(',', '.')) || 0;
                    var total = Math.round(qty * cents(row.querySelector('[data-price]').value));
                    var rate = row.querySelector('[data-vat]').value;
                    row.querySelector('[data-line-total]').textContent = money.format(total / 100);
                    subtotal += total;
                    byRate[rate] = (byRate[rate] || 0) + total;
                });
                var type = form.querySelector('[data-discount-type]').value;
                var value = parseFloat(form.querySelector('[data-discount-value]').value) || 0;
                var discount = type === 'percent' ? Math.round(subtotal * Math.min(value, 100) / 100) : type === 'amount' ? Math.min(Math.round(value * 100), subtotal) : 0;
                var ratio = subtotal ? (subtotal - discount) / subtotal : 0;
                var vat = 0;
                Object.keys(byRate).forEach(function (rate) { vat += Math.round(byRate[rate] * ratio * parseFloat(rate) / 100); });
                var set = function (key, amount) { form.querySelector('[data-total="' + key + '"]').textContent = money.format(amount / 100); };
                set('subtotal', subtotal); set('discount', -discount); set('ht', subtotal - discount); set('vat', vat); set('ttc', subtotal - discount + vat);
            }

            form.querySelector('[data-add-line]').addEventListener('click', function () {
                body.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, next++));
                body.lastElementChild.querySelector('textarea, input').focus();
                recalc();
            });
            body.addEventListener('click', function (event) {
                var button = event.target.closest('[data-remove-line]');
                if (!button) return;
                if (body.children.length === 1) { alert('Un devis doit contenir au moins une ligne.'); return; }
                button.closest('tr').remove();
                recalc();
            });
            form.addEventListener('input', recalc);
            form.addEventListener('change', recalc);
            recalc();
        })();
    </script>
@endpush
