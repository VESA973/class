<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\VehicleAvailabilityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailLogController;
use App\Http\Controllers\EmailSettingsController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\HeroImageController;
use App\Http\Controllers\HomePreviewController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteSettingsController;
use App\Http\Controllers\RequestStatusController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrestationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SitePageController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WebManifestController;
use Illuminate\Support\Facades\Route;

// Accueil : nouvelle page (validee). Pour revenir a l'ancienne : [HomeController::class, 'index'].
Route::get('/', HomePreviewController::class)->name('home');
// Pages au nouveau design ; les anciennes versions restent dans HomeController (vehicles, prestations, contact).
Route::get('/vehicules', [FleetController::class, 'index'])->name('vehicles.page');
Route::get('/vehicules/{vehicle:slug}', [FleetController::class, 'show'])->name('vehicles.show');
Route::get('/prestations', [SitePageController::class, 'prestations'])->name('prestations.page');
Route::get('/contact', [SitePageController::class, 'contact'])->name('contact.page');
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::get('/reserver', [BookingPageController::class, 'create'])->name('booking.create');
Route::permanentRedirect('/nouvelle-accueil', '/');
Route::get('/site.webmanifest', WebManifestController::class)->name('webmanifest');

Route::prefix('api')->name('api.')->group(function (): void {
    Route::get('vehicles/available', [VehicleAvailabilityController::class, 'available'])
        ->middleware('throttle:60,1')
        ->name('vehicles.available');
    Route::get('vehicles/{vehicle}/booked-periods', [VehicleAvailabilityController::class, 'bookedPeriods'])
        ->whereNumber('vehicle')
        ->middleware('throttle:60,1')
        ->name('vehicles.booked-periods');
    Route::post('reservations', [BookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reservations.store');
});

Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'store'])->name('admin.login.store');
Route::post('/admin/logout', [AuthController::class, 'destroy'])->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('vehicles/data', [VehicleController::class, 'data'])->name('vehicles.data');
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::resource('prestations', PrestationController::class)->except(['show']);
    Route::get('settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/logo', [SiteSettingController::class, 'destroy'])->name('settings.logo.destroy');
    Route::get('hero', [HeroImageController::class, 'edit'])->name('hero.edit');
    Route::put('hero', [HeroImageController::class, 'update'])->name('hero.update');
    Route::delete('hero', [HeroImageController::class, 'destroy'])->name('hero.destroy');
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::patch('reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::resource('users', UserController::class)->except(['show']);
    Route::patch('reservations/{reservation}/suivi', RequestStatusController::class)->name('reservations.request-status');
    Route::post('reservations/{reservation}/devis', [QuoteController::class, 'store'])->name('quotes.store');
    Route::get('devis', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('devis/reglages', [QuoteSettingsController::class, 'edit'])->name('quotes.settings');
    Route::put('devis/reglages', [QuoteSettingsController::class, 'update'])->name('quotes.settings.update');
    Route::get('devis/{quote}', [QuoteController::class, 'edit'])->whereNumber('quote')->name('quotes.edit');
    Route::put('devis/{quote}', [QuoteController::class, 'update'])->whereNumber('quote')->name('quotes.update');
    Route::delete('devis/{quote}', [QuoteController::class, 'destroy'])->whereNumber('quote')->name('quotes.destroy');
    Route::get('devis/{quote}/pdf', [QuoteController::class, 'pdf'])->whereNumber('quote')->name('quotes.pdf');
    Route::post('devis/{quote}/envoyer', [QuoteController::class, 'send'])->whereNumber('quote')->middleware('throttle:10,1')->name('quotes.send');
    Route::patch('devis/{quote}/statut', [QuoteController::class, 'status'])->whereNumber('quote')->name('quotes.status');
    Route::get('emails', [EmailSettingsController::class, 'edit'])->name('emails.settings');
    Route::put('emails', [EmailSettingsController::class, 'update'])->name('emails.settings.update');
    Route::post('emails/test', [EmailSettingsController::class, 'test'])->middleware('throttle:10,1')->name('emails.test');
    Route::get('emails/modeles', [EmailTemplateController::class, 'index'])->name('emails.templates');
    Route::get('emails/modeles/{template}', [EmailTemplateController::class, 'edit'])->name('emails.templates.edit');
    Route::put('emails/modeles/{template}', [EmailTemplateController::class, 'update'])->name('emails.templates.update');
    Route::post('emails/modeles/{template}/apercu', [EmailTemplateController::class, 'preview'])->name('emails.templates.preview');
    Route::get('emails/historique', [EmailLogController::class, 'index'])->name('emails.logs');
    Route::get('parametres', [ParametersController::class, 'edit'])->name('parameters.edit');
    Route::put('parametres/maintenance', [ParametersController::class, 'updateMaintenance'])->name('parameters.maintenance');
    Route::get('parametres/maintenance/apercu', [ParametersController::class, 'previewMaintenance'])->name('parameters.maintenance.preview');
    Route::post('parametres/favicon', [ParametersController::class, 'updateFavicon'])->name('parameters.favicon');
    Route::delete('parametres/favicon', [ParametersController::class, 'destroyFavicon'])->name('parameters.favicon.destroy');
    Route::get('planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('planning/events', [PlanningController::class, 'events'])->name('planning.events');
    Route::patch('planning/reservations/{reservation}/status', [PlanningController::class, 'updateStatus'])->name('planning.status');
});
