<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrestationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/vehicules', [HomeController::class, 'vehicles'])->name('vehicles.page');
Route::get('/prestations', [HomeController::class, 'prestations'])->name('prestations.page');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact.page');
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');

Route::get('/admin/login', function () {
    return view('admin.login');
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'password' => ['required', 'string'],
    ]);

    if (! hash_equals((string) config('app.admin_password'), (string) $request->input('password'))) {
        return back()->withErrors(['password' => 'Mot de passe incorrect.']);
    }

    $request->session()->put('admin_authenticated', true);

    return redirect()->route('admin.dashboard');
})->name('admin.login.store');

Route::post('/admin/logout', function (Request $request) {
    $request->session()->forget('admin_authenticated');

    return redirect()->route('home');
})->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function (): void {
    Route::redirect('/', '/admin/vehicles')->name('dashboard');
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::resource('prestations', PrestationController::class)->except(['show']);
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::patch('reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
});
