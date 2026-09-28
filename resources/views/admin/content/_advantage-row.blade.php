<fieldset class="form-card repeater-row" data-repeater-row>
    <legend class="sr-only">Atout</legend>
    <div class="repeater-row-head">
        <strong>Atout <span data-repeater-number></span></strong>
        <span class="repeater-tools">
            <button type="button" class="btn btn-secondary btn-icon" data-repeater-up aria-label="Monter">↑</button>
            <button type="button" class="btn btn-secondary btn-icon" data-repeater-down aria-label="Descendre">↓</button>
            <button type="button" class="btn btn-danger btn-icon" data-repeater-remove aria-label="Supprimer">✕</button>
        </span>
    </div>
    <div class="repeater-fields">
        <label>
            Icône
            <select name="advantages[{{ $index }}][icon]">
                @foreach (\App\Services\SiteContent::ICONS as $icon => $label)
                    <option value="{{ $icon }}" @selected(($advantage['icon'] ?? '') === $icon)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Titre
            <input name="advantages[{{ $index }}][title]" maxlength="80" required value="{{ $advantage['title'] ?? '' }}">
        </label>
        <label class="repeater-wide">
            Texte
            <textarea name="advantages[{{ $index }}][text]" rows="2" maxlength="300" required>{{ $advantage['text'] ?? '' }}</textarea>
        </label>
    </div>
</fieldset>
