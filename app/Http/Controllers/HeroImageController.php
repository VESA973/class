<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HeroImageController extends Controller
{
    public function edit(): View
    {
        return view('admin.hero.edit', [
            'settings' => SiteSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'hero_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'hero_image.required' => 'Choisissez une photo.',
            'hero_image.uploaded' => 'La photo est trop lourde pour le serveur.',
            'hero_image.image' => 'Le fichier doit etre une image.',
            'hero_image.mimes' => 'Formats acceptes : JPG, PNG ou WebP.',
            'hero_image.max' => 'La photo ne doit pas depasser 8 Mo.',
        ]);

        $settings = SiteSetting::current();

        if ($settings->hero_image_path) {
            app(\App\Services\ImageOptimizer::class)->delete($settings->hero_image_path); // photo + version WebP
        }

        $settings->update([
            // Photo plein ecran : 2400 px max, recompressee et doublee d'une version WebP.
            'hero_image_path' => app(\App\Services\ImageOptimizer::class)->optimize($request->file('hero_image')->store('site', 'public'), 2400),
        ]);

        return redirect()
            ->route('admin.hero.edit')
            ->with('status', "Photo d'accueil mise à jour.");
    }

    public function destroy(): RedirectResponse
    {
        $settings = SiteSetting::current();

        if ($settings->hero_image_path) {
            app(\App\Services\ImageOptimizer::class)->delete($settings->hero_image_path); // photo + version WebP
            $settings->update(['hero_image_path' => null]);
        }

        return redirect()
            ->route('admin.hero.edit')
            ->with('status', "Photo d'accueil par défaut rétablie.");
    }
}
