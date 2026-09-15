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
use App\Livewire\Landing;
use App\Livewire\Mortality;
use App\Livewire\Performance;
use App\Livewire\Profile;
use App\Livewire\Reports;
use App\Livewire\Settings;
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

/*
 * The front door depends on who is knocking.
 *
 * The catalogue is the PUBLIC face of the farm, so a visitor who has never
 * signed in lands there. Staff land on the dashboard, which is where their
 * working day starts. Sending everyone to /dashboard meant a member of the
 * public hit the auth middleware and was shown a login form as the first thing
 * the farm's website said to them.
 */
Route::livewire('/', Landing\Index::class)->name('home')->middleware('active');

/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
|
| No `auth`. These are the advertisement surface: anyone may browse the stock
| and open a bird's page without an account, which is the whole point of the
| catalogue existing.
|
| `active` is still applied. It does nothing for a guest - it only acts when
| Auth::check() passes - but it means a signed-in account that gets deactivated
| mid-session is still signed out even while browsing a public page, rather
| than the public pages being a place where deactivation does not take effect.
|
| WHAT A GUEST SEES is not a new visibility tier. The Policies treat a null
| user as the equivalent of an active customer, and the views were already
| written to hide internal fields from that role - see $canSeeInternal in
| broodcocks/show.blade.php. So this opens up who can reach these screens
| without changing what the screens render.
|
| BroodcockPolicy::view() additionally restricts a guest to birds the
| catalogue itself would list: ids in URLs are guessable, and a dead bird or
| another farm's borrowed hen is not something the public should find.
*/
Route::middleware('active')->group(function (): void {
    Route::livewire('/catalog', Catalog\Index::class)->name('catalog.index');

    /*
     * Photo files.
     *
     * The Supabase bucket is private, so photos are streamed through this
     * controller and authorized by BroodcockPhotoPolicy on every request,
     * rather than exposed as unguessable public URLs. That policy is what
     * keeps a guest from paging through photographs of birds the catalogue
     * does not list.
     */
    Route::get('/photos/{photo}', [BroodcockPhotoController::class, 'show'])->name('photos.show');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /* Broodcocks - the central entity. */
    Route::prefix('broodcocks')->name('broodcocks.')->group(function (): void {
        Route::livewire('/', Broodcocks\Index::class)->name('index');
        Route::livewire('/create', Broodcocks\Form::class)->name('create');
        Route::livewire('/{broodcock}/edit', Broodcocks\Form::class)->name('edit');
    });

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
     * VISIT REQUESTS HAVE NO SCREENS, AND NO TABLE EITHER.
     *
     * There used to be two halves here: a public form on the front page that
     * anyone could submit, and this console queue where the farm confirmed,
     * declined or marked a request visited. The farm asked for the form to go -
     * they would rather be rung - so the front page now carries the phone
     * number, the email address, where to find the farm and its visiting
     * hours, and nothing that submits.
     *
     * The queue went with it, and that is the part worth explaining. Nothing
     * else in this system could create an appointment: the console screen only
     * ever read, decided and deleted. Keeping it would have left an inbox with
     * the form that fed it removed - reachable from the sidebar, permanently
     * empty after the last request was cleared, and impossible to explain to
     * anyone who asked what it was for.
     *
     * Removed with it: the model, policy, status enum, factory, seeder and the
     * three test files, plus the table (2026_09_15_090000). This is deliberately
     * the whole feature and not the half-removal the pens note below describes,
     * because that was the lesson. It is one `git revert` from coming back.
     */

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
     * The farm's public identity - OWNER ONLY.
     *
     * Its name, phone number, email, address and visiting hours, which used to
     * be GFMS_FARM_* environment variables. That put the farm's own telephone
     * number behind a code change and a deploy, and therefore behind a
     * developer: the owner, whose number it is and who knows when it changes,
     * was the one person who could not change it.
     *
     * FarmSettingPolicy is what enforces owner-only; this group only gets a
     * signed-in user as far as the component, which authorizes in mount().
     */
    Route::livewire('/settings', Settings\Farm::class)->name('settings.edit');

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

/*
|--------------------------------------------------------------------------
| PUBLIC BIRD PAGES
|--------------------------------------------------------------------------
|
| DECLARED LAST, AND THAT IS LOAD-BEARING. Laravel matches routes in
| registration order, and `/broodcocks/{broodcock}` will happily match the
| string "create". The auth group above registers /broodcocks/create and
| /broodcocks/{broodcock}/edit first, so those still win; anything else falls
| through to here.
|
| Moving either of these above that group would make /broodcocks/create open a
| bird whose id is the word "create" - a 404 that looks like a missing record
| rather than a routing mistake.
|
| The pedigree is public deliberately: the three-generation family tree is the
| farm's actual selling point, and hiding it behind a login would leave the
| advertisement showing photographs of birds with nothing to say about their
| breeding.
*/
Route::middleware('active')->prefix('broodcocks')->name('broodcocks.')->group(function (): void {
    Route::livewire('/{broodcock}/pedigree', Broodcocks\Pedigree::class)->name('pedigree');
    Route::livewire('/{broodcock}', Broodcocks\Show::class)->name('show');
});
