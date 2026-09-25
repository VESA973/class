<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use App\Services\CookieSettings;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin > Cookies : bandeau, categories et scripts, registre des consentements. */
class CookieAdminController extends Controller
{
    public function edit(CookieSettings $cookies): View
    {
        return view('admin.cookies.edit', ['config' => $cookies->all()]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $hex = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $data = $request->validate([
            'texts.title' => ['required', 'string', 'max:120'],
            'texts.message' => ['required', 'string', 'max:1000'],
            'texts.accept' => ['required', 'string', 'max:40'],
            'texts.reject' => ['required', 'string', 'max:40'],
            'texts.customize' => ['required', 'string', 'max:40'],
            'texts.save' => ['required', 'string', 'max:40'],
            'colors.background' => $hex, 'colors.text' => $hex, 'colors.button' => $hex, 'colors.button_text' => $hex,
            'categories.*.label' => ['required', 'string', 'max:80'],
            'categories.*.description' => ['required', 'string', 'max:500'],
            'categories.*.scripts' => ['nullable', 'string', 'max:20000'],
            'categories.*.cookies' => ['nullable', 'string', 'max:500'],
        ], ['colors.*.regex' => 'Couleur invalide (format #RRGGBB).']);

        $settings->set([
            'cookies.enabled' => $request->boolean('enabled'),
            'cookies.texts' => $data['texts'],
            'cookies.colors' => $data['colors'] ?? [],
            'cookies.categories' => array_intersect_key($data['categories'] ?? [], array_flip(CookieSettings::CATEGORIES)),
        ]);

        return redirect()->route('admin.cookies.edit')->with('status', 'Bandeau cookies enregistré.');
    }

    /** Nouvelle version de la politique : le choix est redemande a tous les visiteurs. */
    public function renew(Settings $settings, CookieSettings $cookies): RedirectResponse
    {
        $settings->set(['cookies.policy_version' => $cookies->all()['version'] + 1]);

        return back()->with('status', 'Le consentement sera redemandé à tous les visiteurs.');
    }

    public function registry(Request $request, CookieSettings $cookies): View
    {
        $since = now()->subDays(30);
        $stats = CookieConsent::query()->where('created_at', '>=', $since)
            ->selectRaw('action, count(*) as total')->groupBy('action')->pluck('total', 'action');

        return view('admin.cookies.registry', [
            'consents' => CookieConsent::query()
                ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
                ->latest('id')->paginate(30)->withQueryString(),
            'stats' => $stats,
            'total' => $stats->sum(),
            'version' => $cookies->all()['version'],
        ]);
    }
}
