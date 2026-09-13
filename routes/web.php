<?php

declare(strict_types=1);

use App\Http\Controllers\BroodcockPhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignGalleryController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\ReportController;
use App\Livewire\Breeding;
use App\Livewire\Broodcocks;
use App\Livewire\Catalog;
use App\Livewire\Health;
use App\Livewire\Mortality;
use App\Livewire\Performance;
use App\Livewire\Profile;
use App\Livewire\Reports;
use App\Livewire\Users;
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
| a screen at all. The real permission checks live in the Policies and run per
| action - a `staff` user can open /broodcocks/1 but still cannot delete it,
| and that is enforced in BroodcockPolicy, not here.
|
| Livewire 4 uses Route::livewire(), not Route::get(), to route straight to a
| component.
|
| ORDERING: literal segments such as /create are always declared BEFORE the
| wildcard {model} route, otherwise "/broodcocks/create" is matched as a bird
| whose id is the string "create".
|
*/

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
     * Customer portal - the read-only catalogue.
     *
     * Open to every signed-in role (staff use it to see what a customer sees),
     * but it is the only farm screen a customer has any reason to visit.
     */
    Route::livewire('/catalog', Catalog\Index::class)->name('catalog.index');

    /* Broodcocks - the central entity. Visible to every role. */
    Route::prefix('broodcocks')->name('broodcocks.')->group(function (): void {
        Route::livewire('/', Broodcocks\Index::class)->name('index');
        Route::livewire('/create', Broodcocks\Form::class)->name('create');
        Route::livewire('/{broodcock}/edit', Broodcocks\Form::class)->name('edit');
        Route::livewire('/{broodcock}/pedigree', Broodcocks\Pedigree::class)->name('pedigree');
        Route::livewire('/{broodcock}', Broodcocks\Show::class)->name('show');
    });

    /*
     * Photo files.
     *
     * The Supabase bucket is private, so photos are streamed through this
     * controller and authorized by BroodcockPhotoPolicy on every request,
     * rather than exposed as unguessable public URLs.
     */
    Route::get('/photos/{photo}', [BroodcockPhotoController::class, 'show'])->name('photos.show');

    /* Health records - customers may view, only staff may write. */
    Route::prefix('health')->name('health.')->group(function (): void {
        Route::livewire('/', Health\Index::class)->name('index');
        Route::livewire('/schedule', Health\Schedule::class)->name('schedule');
        Route::livewire('/create', Health\Form::class)->name('create');
        Route::livewire('/{record}/edit', Health\Form::class)->name('edit');
    });

    /* Performance records - customers may view, only staff may write. */
    Route::prefix('performance')->name('performance.')->group(function (): void {
        Route::livewire('/', Performance\Index::class)->name('index');
        Route::livewire('/create', Performance\Form::class)->name('create');
        Route::livewire('/{record}/edit', Performance\Form::class)->name('edit');
    });

    /* Breeding records - internal only. */
    Route::prefix('breeding')->name('breeding.')->group(function (): void {
        Route::livewire('/', Breeding\Index::class)->name('index');
        Route::livewire('/create', Breeding\Form::class)->name('create');
        Route::livewire('/{record}/edit', Breeding\Form::class)->name('edit');
        Route::livewire('/{record}', Breeding\Show::class)->name('show');
    });

    /* Mortality - internal only. */
    Route::prefix('mortality')->name('mortality.')->group(function (): void {
        Route::livewire('/', Mortality\Index::class)->name('index');
        // The broodcock is optional so the form can be reached either from the
        // mortality list or straight from a bird's page.
        Route::livewire('/create/{broodcock?}', Mortality\Form::class)->name('create');
    });

    /*
     * Reports - internal only.
     *
     * The CSV and PDF endpoints are plain GET routes rather than Livewire
     * actions because a file download cannot be delivered through a Livewire
     * update. ReportController authorizes, exports and writes the audit row.
     */
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::livewire('/', Reports\Index::class)->name('index');
        Route::get('/{report}/csv', [ReportController::class, 'csv'])->name('csv');
        Route::get('/{report}/pdf', [ReportController::class, 'pdf'])->name('pdf');
    });

    /*
     * User management - OWNER ONLY.
     *
     * There is no self-registration anywhere in this system; every account is
     * created here. UserPolicy denies staff and customers outright.
     */
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::livewire('/', Users\Index::class)->name('index');
        Route::livewire('/create', Users\Form::class)->name('create');
        Route::livewire('/{user}/edit', Users\Form::class)->name('edit');
    });

    /*
     * PENS HAVE NO SCREENS, DELIBERATELY.
     *
     * Pens are still real data - `broodcocks.pen_id`, the Pen model, the Pen
     * column and filter on the inventory report, and the pen selector when
     * registering a hatch on /breeding/{record}. What was removed is the CRUD
     * around them, which had been dropped from the sidebar in 0f8c244 while its
     * routes stayed live: reachable by typing a URL, covered by 34 tests, and
     * findable by nobody. Half-removed was the worst of both.
     *
     * CONSEQUENCE, stated plainly: pens can no longer be created or renamed
     * through the interface. They come from PenSeeder. If the farm needs a new
     * pen, that is a seeder change and a deploy - which is the right trade
     * while pens are a fixed handful, and the wrong one the day they are not.
     * Restoring the screens means restoring this group and the four components
     * with it; they are in the history at 0f8c244.
     */

    /*
     * A user's own profile. Not inside the users.* group on purpose: that group
     * is the owner administering OTHER accounts, and this is every signed-in
     * user editing their own. Different authorization, different route.
     */
    Route::livewire('/profile', Profile\Edit::class)->name('profile.edit');

    /*
     * The component gallery. Not a feature - it is the reference the interface
     * is built against, and the page to walk a panel through when asked to
     * justify the visual decisions. Internal-only; the controller re-checks
     * rather than trusting the group, since this sits outside the policy layer.
     */
    Route::get('/design', DesignGalleryController::class)->name('design');

    /*
     * Environment diagnostics. OWNER ONLY.
     *
     * Render's free plan gives no shell and no one-off jobs, so there is
     * otherwise no way to ask the running container which extensions loaded,
     * whether a directory is writable, or whether the storage bucket can be
     * reached. Every production-only failure on this deployment has been one
     * of those, and all of them are invisible from the outside.
     *
     * It reports no secret VALUES - only whether each credential is set.
     */
    Route::get('/diagnostics', DiagnosticsController::class)->name('diagnostics');
});
