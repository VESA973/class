<?php

use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\MaintenanceMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mode maintenance pilote depuis l'admin (apres la session : les admins connectes passent).
        $middleware->web(append: [MaintenanceMode::class]);

        // Redirections 301/302 de l'admin (SEO), appliquees avant le routage.
        $middleware->prepend(HandleRedirects::class);

        $middleware->alias([
            'admin.auth' => EnsureAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
