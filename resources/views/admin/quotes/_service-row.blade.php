<fieldset class="form-card repeater-row" data-repeater-row>
    <legend class="sr-only">Service</legend>
    <div class="repeater-row-head">
        <strong>Service <span data-repeater-number></span></strong>
        <span class="repeater-tools">
            <button type="button" class="btn btn-secondary btn-icon" data-repeater-up aria-label="Monter">↑</button>
            <button type="button" class="btn btn-secondary btn-icon" data-repeater-down aria-label="Descendre">↓</button>
            <button type="button" class="btn btn-danger btn-icon" data-repeater-remove aria-label="Supprimer">✕</button>
        </span>
    </div>
    <div class="service-fields">
        <label class="service-name">
            Nom
            <input name="services[{{ $index }}][name]" maxlength="120" required value="{{ $service['name'] ?? '' }}" placeholder="Ex. Heures supplémentaires">
        </label>
        <label>
            Prix (€)
            <input type="number" step="0.01" min="0" name="services[{{ $index }}][price]" required value="{{ $service['price'] ?? '' }}">
        </label>
        <label>
            Unité
            <select name="services[{{ $index }}][unit]">
                @foreach (\App\Services\QuoteService::SERVICE_UNITS as $unit => $label)
                    <option value="{{ $unit }}" @selected(($service['unit'] ?? 'forfait') === $unit)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Automatique au-delà de (heures)
            <input type="number" min="1" max="720" step="1" name="services[{{ $index }}][auto_after_hours]" value="{{ $service['auto_after_hours'] ?? '' }}" placeholder="Vide = manuel">
        </label>
    </div>
</fieldset>
