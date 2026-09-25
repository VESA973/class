<?php

namespace App\Http\Controllers;

use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/** /site.webmanifest : icones pour l'ajout a l'ecran d'accueil (Android, Chrome). */
class WebManifestController extends Controller
{
    public function __invoke(Settings $settings): JsonResponse
    {
        $version = $settings->get('favicon.version');
        $icons = [];

        if ($version) {
            foreach ([192, 512] as $size) {
                $icons[] = [
                    'src' => Storage::disk('public')->url("favicon/android-chrome-{$size}x{$size}.png").'?v='.$version,
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                ];
            }
        }

        return response()->json([
            'name' => 'CLASS’AFFAIRE',
            'short_name' => 'CLASS’AFFAIRE',
            'icons' => $icons,
            'theme_color' => '#0a0a0a',
            'background_color' => '#0a0a0a',
            'display' => 'standalone',
            'start_url' => '/',
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
