<?php

namespace App\Http\Controllers;

use App\Services\FaviconGenerator;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\IpUtils;

/** Parametres > Favicon & maintenance. */
class ParametersController extends Controller
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function edit(Request $request): View
    {
        return view('admin.parameters.edit', [
            'maintenance' => [
                'enabled' => (bool) $this->settings->get('maintenance.enabled', false),
                'title' => $this->settings->get('maintenance.title', ''),
                'message' => $this->settings->get('maintenance.message', ''),
                'return_at' => $this->settings->get('maintenance.return_at', ''),
                'allowed_ips' => implode("\n", (array) $this->settings->get('maintenance.allowed_ips', [])),
            ],
            'favicon' => [
                'version' => $this->settings->get('favicon.version'),
                'has_svg' => (bool) $this->settings->get('favicon.has_svg', false),
            ],
            'svgSupported' => FaviconGenerator::svgSupported(),
            'currentIp' => $request->ip(),
        ]);
    }

    /** Apercu de la page de maintenance telle que la verront les visiteurs (code 200, reserve a l'admin). */
    public function previewMaintenance(): View
    {
        $returnAt = $this->settings->get('maintenance.return_at');

        return view('maintenance', [
            'title' => $this->settings->get('maintenance.title') ?: 'Site en maintenance',
            'message' => $this->settings->get('maintenance.message') ?: 'Nous améliorons notre site. Merci de revenir un peu plus tard.',
            'returnAt' => $returnAt ? \Carbon\Carbon::parse($returnAt, config('app.local_timezone')) : null,
        ]);
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:1000'],
            'return_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'allowed_ips' => ['nullable', 'string', 'max:2000'],
        ], [
            'return_at.date_format' => 'Date de retour invalide.',
        ]);

        // Une IP (ou plage CIDR) par ligne ou separee par des virgules.
        $ips = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) ($validated['allowed_ips'] ?? ''))))));

        foreach ($ips as $ip) {
            if (! $this->isValidIp($ip)) {
                throw ValidationException::withMessages(['allowed_ips' => "Adresse IP invalide : {$ip}"]);
            }
        }

        $enabled = $request->boolean('enabled');

        $this->settings->set([
            'maintenance.enabled' => $enabled,
            'maintenance.title' => $validated['title'] ?? '',
            'maintenance.message' => $validated['message'] ?? '',
            'maintenance.return_at' => $validated['return_at'] ?? '',
            'maintenance.allowed_ips' => $ips,
        ]);

        return redirect()
            ->route('admin.parameters.edit')
            ->with('status', $enabled
                ? 'Mode maintenance ACTIVÉ : les visiteurs voient la page de maintenance.'
                : 'Mode maintenance désactivé : le site est en ligne.');
    }

    public function updateFavicon(Request $request, FaviconGenerator $generator): RedirectResponse
    {
        $request->validate([
            'favicon' => ['required', 'file', 'extensions:png,svg', 'mimetypes:image/png,image/svg+xml,text/xml,text/plain', 'max:2048'],
        ], [
            'favicon.required' => 'Choisissez une image.',
            'favicon.extensions' => 'Le favicon doit être un PNG ou un SVG.',
            'favicon.mimetypes' => 'Le favicon doit être un PNG ou un SVG.',
            'favicon.max' => 'Le favicon ne doit pas dépasser 2 Mo.',
            'favicon.uploaded' => 'Le fichier est trop lourd pour le serveur.',
        ]);

        $file = $request->file('favicon');

        if ($file->getClientOriginalExtension() === 'png') {
            [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];

            if (min($width, $height) < 192) {
                throw ValidationException::withMessages(['favicon' => "Image trop petite ({$width}×{$height}) : 192×192 minimum, 512×512 idéalement."]);
            }
        }

        try {
            $result = $generator->generate($file);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['favicon' => $exception->getMessage()]);
        }

        $this->settings->set([
            'favicon.version' => now()->timestamp,
            'favicon.has_svg' => $result['has_svg'],
        ]);

        return redirect()->route('admin.parameters.edit')->with('status', 'Favicon mis à jour : toutes les tailles ont été générées.');
    }

    public function destroyFavicon(FaviconGenerator $generator): RedirectResponse
    {
        $generator->delete();
        $this->settings->forget('favicon.version', 'favicon.has_svg');

        return redirect()->route('admin.parameters.edit')->with('status', 'Favicon personnalisé supprimé : l’icône par défaut est rétablie.');
    }

    private function isValidIp(string $ip): bool
    {
        [$address] = explode('/', $ip, 2);

        return filter_var($address, FILTER_VALIDATE_IP) !== false && IpUtils::checkIp($address, $ip);
    }
}
