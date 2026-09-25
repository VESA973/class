<?php

use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->web(append: [MaintenanceMode::class, SecurityHeaders::class]);

        // Redirections 301/302 de l'admin (SEO), appliquees avant le routage.
        $middleware->prepend(HandleRedirects::class);

        // Admin : la connexion est verifiee AVANT la recherche des elements en base
        // (un visiteur non connecte ne peut pas deviner quels identifiants existent).
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: EnsureAdminAuthenticated::class,
        );

        $middleware->alias([
            'admin.auth' => EnsureAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
