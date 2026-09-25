<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique les redirections de l'admin (SEO > Redirections) avant le routage,
 * y compris pour des adresses qui n'existent plus. L'administration n'est jamais redirigee.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = RedirectService::normalize($request->getPathInfo());

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return $next($request);
        }

        $redirect = RedirectService::map()[$path] ?? null;

        if (! $redirect) {
            return $next($request);
        }

        Redirect::query()->whereKey($redirect['id'])->increment('hits', 1, ['last_hit_at' => now()]);

        $target = str_starts_with($redirect['to'], 'http') ? $redirect['to'] : url($redirect['to']);

        if ($query = $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?').$query;
        }

        return redirect()->away($target, $redirect['code']);
    }
}
