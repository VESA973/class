<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Vehicle;
use Illuminate\View\View;

/**
 * Page d'accueil du site (route "/"), validee le 24/09/2026.
 * L'ancien accueil (HomeController@index + welcome.blade.php) est conserve pour un retour arriere.
 */
class HomePreviewController extends Controller
{
    public function __invoke(): View
    {
        // "Populaires" : les plus demandes (hors annulations), puis les plus prestigieux.
        $vehicles = Vehicle::query()
            ->where('is_available', true)
            ->withCount(['reservations' => fn ($query) => $query->where('status', '!=', 'cancelled')])
            ->orderByDesc('reservations_count')
            ->orderByDesc('daily_price')
            ->limit(6)
            ->get();

        $settings = SiteSetting::current();

        return view('pages.home-v2', [
            'siteSettings' => $settings,
            'heroImageUrl' => $settings->hero_image_url,
            'vehicles' => $vehicles,
            'content' => config('home'),
        ]);
    }
}
