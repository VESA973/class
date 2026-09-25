<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Une seule instance par requete : les parametres ne sont lus qu'une fois.
        $this->app->singleton(\App\Services\Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Seules les listes de l'admin sont paginees.
        Paginator::defaultView('admin.partials.pagination');

        // Serveur d'envoi des emails regle depuis l'admin (sinon : fichier .env).
        $this->app->make(\App\Services\MailSettings::class)->apply();

        View::composer(['layouts.site', 'layouts.modern'], function ($view): void {
            $settings = null;

            if (Schema::hasTable('site_settings')) {
                $settings = SiteSetting::current();
            }

            $view->with('siteSettings', $settings);
        });
    }
}
