<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => SiteSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'max:4096'],
            'logo_width' => ['required', 'integer', 'min:32', 'max:220'],
        ]);

        $settings = SiteSetting::current();

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('site', 'public');
            unset($validated['logo']);
        }

        unset($validated['logo']);
        $settings->update($validated);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Logo mis à jour.');
    }

    public function destroy(): RedirectResponse
    {
        $settings = SiteSetting::current();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->update(['logo_path' => null]);
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Logo supprimé.');
    }
}
