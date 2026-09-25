<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\VehicleAvailabilityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingPageController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\HeroImageController;
use App\Http\Controllers\HomePreviewController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrestationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SitePageController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
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
    Route::redirect('/', '/admin/vehicles')->name('dashboard');
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
    Route::get('planning', [PlanningController::class, 'index'])->name('planning.index');
    Route::get('planning/events', [PlanningController::class, 'events'])->name('planning.events');
    Route::patch('planning/reservations/{reservation}/status', [PlanningController::class, 'updateStatus'])->name('planning.status');
});
