<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mode maintenance pilote depuis l'admin (Parametres > Favicon & maintenance).
 *
 * - Visiteurs : page de maintenance, HTTP 503 + Retry-After (Google revient plus tard sans penaliser le site).
 * - Toujours accessibles : le back-office (/admin...), les administrateurs connectes et les IP autorisees.
 */
class MaintenanceMode
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->get('maintenance.enabled', false)) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*') || $this->isAllowed($request)) {
            // Rappel visuel pour l'administrateur qui navigue sur le site pendant la maintenance.
            View::share('maintenanceBypass', true);

            return $next($request);
        }

        $retryAfter = $this->retryAfterSeconds();
        $headers = ['Retry-After' => $retryAfter, 'Cache-Control' => 'no-store'];

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Site en maintenance, merci de réessayer plus tard.'], 503, $headers);
        }

        return response()->view('maintenance', [
            'title' => $this->settings->get('maintenance.title') ?: 'Site en maintenance',
            'message' => $this->settings->get('maintenance.message') ?: 'Nous améliorons notre site. Merci de revenir un peu plus tard.',
            'returnAt' => $this->returnAt(),
        ], 503, $headers);
    }

    private function isAllowed(Request $request): bool
    {
        $user = $request->user();

        if ($user && $user->is_active) {
            return true;
        }

        $ips = array_filter((array) $this->settings->get('maintenance.allowed_ips', []));

        return $ips !== [] && IpUtils::checkIp((string) $request->ip(), $ips);
    }

    private function returnAt(): ?Carbon
    {
        $value = $this->settings->get('maintenance.return_at');

        try {
            return $value ? Carbon::parse($value, 'Europe/Paris') : null;
        } catch (Throwable) {
            return null;
        }
    }

    /** Secondes avant le retour prevu (au moins 5 min), 1 h si aucune date n'est indiquee. */
    private function retryAfterSeconds(): int
    {
        $returnAt = $this->returnAt();

        if (! $returnAt || $returnAt->isPast()) {
            return 3600;
        }

        return max(300, (int) now()->diffInSeconds($returnAt));
    }
}
