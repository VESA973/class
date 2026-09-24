<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($user) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Appels du planning (fetch JSON) : erreur 401 plutot qu'une redirection HTML.
            if ($request->expectsJson()) {
                abort(401, 'Session expirée.');
            }

            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
