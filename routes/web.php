<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Authentication routes (/login, /logout, /forgot-password, /reset-password,
| /user/confirm-password) are registered by Laravel Fortify - see
| App\Providers\FortifyServiceProvider, which binds our own Blade views to
| them. Registration is deliberately disabled in config/fortify.php.
|
| NOTE: authorization is NOT performed here. Route middleware only decides
| whether a request reaches a screen at all; the actual permission checks live
| in the Policies and are applied per action.
|
*/

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
