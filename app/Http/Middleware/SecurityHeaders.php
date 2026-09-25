<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** En-tetes de securite HTTP ajoutes a toutes les pages. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');           // pas d'interpretation de type par le navigateur
        $headers->set('X-Frame-Options', 'SAMEORIGIN');               // le site ne peut pas etre integre par un autre site
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Fonctions sensibles desactivees ; la realite augmentee des fiches vehicules reste autorisee.
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), xr-spatial-tracking=(self)');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
