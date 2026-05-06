<?php

namespace App\Providers;

use App\Models\SiteSetting;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.site', function ($view): void {
            $settings = null;

            if (Schema::hasTable('site_settings')) {
                $settings = SiteSetting::current();
            }

            $view->with('siteSettings', $settings);
        });
    }
}
