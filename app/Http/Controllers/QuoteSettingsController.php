<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\QuoteService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > Devis > Reglages (modele par defaut, TVA, entreprise, envoi automatique) et Services (forfaits). */
class QuoteSettingsController extends Controller
{
    public function editServices(QuoteService $quotes): View
    {
        return view('admin.quotes.services', ['services' => $quotes->config()['services'], 'config' => $quotes->config()]);
    }

    public function updateServices(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'services' => ['array', 'max:50'],
            'services.*.name' => ['required', 'string', 'max:120'],
            'services.*.price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'services.*.unit' => ['required', Rule::in(array_keys(QuoteService::SERVICE_UNITS))],
            'services.*.auto_after_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ], [
            'services.*.name.required' => 'Chaque service doit avoir un nom.',
            'services.*.price.required' => 'Chaque service doit avoir un prix (0 accepté).',
            'services.*.auto_after_hours.integer' => 'Le seuil doit être un nombre d’heures entier.',
        ]);

        $settings->set(['quotes.services' => array_values(array_map(fn (array $service) => [
            'name' => trim($service['name']),
            'price' => round((float) $service['price'], 2),
            'unit' => $service['unit'],
            'auto_after_hours' => isset($service['auto_after_hours']) && $service['auto_after_hours'] !== '' ? (int) $service['auto_after_hours'] : null,
        ], $data['services'] ?? []))]);

        return redirect()->route('admin.quotes.services')->with('status', 'Services des devis enregistrés.');
    }

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
            'vat_mention' => ['nullable', 'string', 'max:255'],
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
            'quotes.vat_enabled' => $request->boolean('vat_enabled'),
            'quotes.vat_mention' => trim((string) ($data['vat_mention'] ?? '')),
            'quotes.auto_send' => $request->boolean('auto_send'),
            'quotes.company' => array_map(fn ($value) => $value ?? '', $data['company'] ?? []),
        ]);

        return redirect()->route('admin.quotes.settings')->with('status', $request->boolean('auto_send')
            ? 'Réglages enregistrés. ATTENTION : l’envoi automatique des devis est ACTIVÉ.'
            : 'Réglages des devis enregistrés.');
    }
}
