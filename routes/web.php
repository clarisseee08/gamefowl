<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Livewire\Broodcocks;
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
| NOTE ON AUTHORIZATION: middleware here only decides whether a request reaches
| a screen at all. The actual permission checks live in the Policies and run
| per action - a `staff` user can reach /broodcocks/1 but still cannot delete
| it, and that is enforced in BroodcockPolicy, not here.
|
| Livewire 4 uses Route::livewire(), not Route::get(), to route directly to a
| component.
|
*/

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
     * Broodcocks - the central entity.
     *
     * `create` and `edit` are declared BEFORE the wildcard `{broodcock}` show
     * route, otherwise "/broodcocks/create" would be matched as a bird whose
     * id is the literal string "create".
     */
    Route::prefix('broodcocks')->name('broodcocks.')->group(function (): void {
        Route::livewire('/', Broodcocks\Index::class)->name('index');
        Route::livewire('/create', Broodcocks\Form::class)->name('create');
        Route::livewire('/{broodcock}/edit', Broodcocks\Form::class)->name('edit');
        Route::livewire('/{broodcock}/pedigree', Broodcocks\Pedigree::class)->name('pedigree');
        Route::livewire('/{broodcock}', Broodcocks\Show::class)->name('show');
    });
});
