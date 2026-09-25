<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\View\View;

/**
 * Page d'accueil du site (route "/"), validee le 24/09/2026.
 * L'ancien accueil (HomeController@index + welcome.blade.php) est conserve pour un retour arriere.
 */
class HomePreviewController extends Controller
{
    public function __invoke(): View
    {
        $settings = SiteSetting::current();

        return view('pages.home-v2', [
            'siteSettings' => $settings,
            'heroImageUrl' => $settings->hero_image_url,
            'content' => config('home'),
        ]);
    }
}
