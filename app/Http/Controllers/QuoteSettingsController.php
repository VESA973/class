<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\QuoteService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > Devis > Reglages : modele de devis par defaut, entreprise, envoi automatique (desactive). */
class QuoteSettingsController extends Controller
{
    public function edit(QuoteService $quotes): View
    {
        return view('admin.quotes.settings', ['config' => $quotes->config(), 'vatRates' => Quote::VAT_RATES]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'vat_rate' => ['required', Rule::in(array_keys(Quote::VAT_RATES))],
            'validity_days' => ['required', 'integer', 'between:1,365'],
            'line_template' => ['required', 'string', 'max:500'],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'company.name' => ['nullable', 'string', 'max:255'],
            'company.legal_form' => ['nullable', 'string', 'max:255'],
            'company.siret' => ['nullable', 'string', 'max:30'],
            'company.vat_number' => ['nullable', 'string', 'max:30'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.phone' => ['nullable', 'string', 'max:40'],
            'company.iban' => ['nullable', 'string', 'max:50'],
        ]);

        $settings->set([
            'quotes.vat_rate' => $data['vat_rate'],
            'quotes.validity_days' => (int) $data['validity_days'],
            'quotes.line_template' => $data['line_template'],
            'quotes.conditions' => $data['conditions'] ?? '',
            'quotes.prices_include_vat' => $request->boolean('prices_include_vat'),
            'quotes.auto_send' => $request->boolean('auto_send'),
            'quotes.company' => array_map(fn ($value) => $value ?? '', $data['company'] ?? []),
        ]);

        return redirect()->route('admin.quotes.settings')->with('status', $request->boolean('auto_send')
            ? 'Réglages enregistrés. ATTENTION : l’envoi automatique des devis est ACTIVÉ.'
            : 'Réglages des devis enregistrés.');
    }
}
