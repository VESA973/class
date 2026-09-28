<?php

namespace App\Http\Controllers;

use App\Services\Settings;
use App\Services\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin : atouts de la page d'accueil et types de prestation de la reservation. */
class SiteContentController extends Controller
{
    public function editAdvantages(SiteContent $content): View
    {
        return view('admin.content.advantages', ['advantages' => $content->advantages()]);
    }

    public function updateAdvantages(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'advantages' => ['array', 'max:'.SiteContent::MAX_ADVANTAGES],
            'advantages.*.icon' => ['required', Rule::in(array_keys(SiteContent::ICONS))],
            'advantages.*.title' => ['required', 'string', 'max:80'],
            'advantages.*.text' => ['required', 'string', 'max:300'],
        ], [
            'advantages.max' => 'Au maximum '.SiteContent::MAX_ADVANTAGES.' atouts.',
            'advantages.*.title.required' => 'Chaque atout doit avoir un titre.',
            'advantages.*.text.required' => 'Chaque atout doit avoir un texte.',
        ]);

        $settings->set(['home.advantages' => array_values(array_map(fn (array $item) => [
            'icon' => $item['icon'],
            'title' => trim($item['title']),
            'text' => trim($item['text']),
        ], $data['advantages'] ?? []))]);

        return redirect()->route('admin.content.advantages')->with('status', 'Atouts de la page d’accueil enregistrés.');
    }

    public function resetAdvantages(Settings $settings): RedirectResponse
    {
        $settings->forget('home.advantages');

        return redirect()->route('admin.content.advantages')->with('status', 'Atouts d’origine rétablis.');
    }

    public function editServiceTypes(SiteContent $content): View
    {
        return view('admin.content.service-types', ['types' => $content->serviceTypes()]);
    }

    public function updateServiceTypes(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'types' => ['array', 'max:'.SiteContent::MAX_SERVICE_TYPES],
            'types.*' => ['nullable', 'string', 'max:120'],
        ], [
            'types.max' => 'Au maximum '.SiteContent::MAX_SERVICE_TYPES.' types de prestation.',
            'types.*.max' => 'Un type de prestation fait 120 caractères au maximum.',
        ]);

        // Lignes vides ignorees, doublons retires (sans tenir compte des majuscules).
        $types = collect($data['types'] ?? [])
            ->map(fn ($type) => trim((string) $type))
            ->filter()
            ->unique(fn ($type) => mb_strtolower($type))
            ->values()
            ->all();

        $settings->set(['booking.service_types' => $types]);

        return redirect()->route('admin.content.service-types')->with('status', 'Types de prestation enregistrés.');
    }
}
