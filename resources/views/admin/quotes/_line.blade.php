<tr>
    <td><textarea name="lines[{{ $index }}][description]" rows="2" maxlength="500" required aria-label="Désignation">{{ $line['description'] }}</textarea></td>
    <td><input type="number" step="0.01" min="0.01" name="lines[{{ $index }}][quantity]" value="{{ $line['quantity'] }}" required aria-label="Quantité" data-qty style="min-width:70px"></td>
    <td><input type="number" step="0.01" min="0" name="lines[{{ $index }}][unit_price_ht]" value="{{ $line['unit_price_ht'] }}" required aria-label="Prix unitaire HT" data-price style="min-width:100px"></td>
    <td>
        <select name="lines[{{ $index }}][vat_rate]" aria-label="Taux de TVA" data-vat style="min-width:110px">
            @foreach (\App\Models\Quote::VAT_RATES as $rate => $label)
                <option value="{{ $rate }}" @selected((string) $line['vat_rate'] === (string) $rate)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td data-line-total style="white-space:nowrap">—</td>
    <td><button type="button" class="link-button" data-remove-line aria-label="Supprimer la ligne" style="color:var(--danger)">✕</button></td>
</tr>
