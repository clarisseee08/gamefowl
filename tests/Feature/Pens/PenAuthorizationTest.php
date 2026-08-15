<?php

declare(strict_types=1);

namespace Tests\Feature\Pens;

use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Livewire\Pens\AssignBroodcocks;
use App\Livewire\Pens\Form;
use App\Livewire\Pens\Index;
use App\Livewire\Pens\Show;
use App\Models\Broodcock;
use App\Models\Pen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Who may do what with a pen.
 *
 * Pens are internal farm logistics: customers must never see them at all, and
 * only the owner may delete one. Each test asserts the HTTP status *and* that
 * the data did not move, because a 403 that still wrote is not a 403.
 */
final class PenAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerPenRoutes();
    }

    // -----------------------------------------------------------------
    // Guests
    // -----------------------------------------------------------------

    public function test_a_guest_is_sent_to_the_login_screen(): void
    {
        $pen = Pen::factory()->create();

        $this->get(route('pens.index'))->assertRedirect('/login');
        $this->get(route('pens.show', $pen))->assertRedirect('/login');
        $this->get(route('pens.create'))->assertRedirect('/login');
    }

    // -----------------------------------------------------------------
    // Customers - pens are internal, they must not exist for them
    // -----------------------------------------------------------------

    public function test_a_customer_cannot_see_the_pen_list(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('pens.index'))->assertForbidden();
    }

    public function test_a_customer_cannot_see_a_single_pen(): void
    {
        $customer = User::factory()->customer()->create();
        $pen = Pen::factory()->create(['code' => 'P-01']);

        $this->actingAs($customer)->get(route('pens.show', $pen))->assertForbidden();
    }

    public function test_a_customer_cannot_open_the_create_or_edit_forms(): void
    {
        $customer = User::factory()->customer()->create();
        $pen = Pen::factory()->create();

        $this->actingAs($customer)->get(route('pens.create'))->assertForbidden();
        $this->actingAs($customer)->get(route('pens.edit', $pen))->assertForbidden();
    }

    public function test_a_customer_cannot_create_a_pen(): void
    {
        $customer = User::factory()->customer()->create();

        Livewire::actingAs($customer)
            ->test(Form::class)
            ->assertForbidden();

        $this->assertSame(0, Pen::count());
    }

    public function test_a_customer_cannot_move_birds_between_pens(): void
    {
        $customer = User::factory()->customer()->create();
        $pen = Pen::factory()->create();
        $bird = $this->makeBroodcock('Unmoved', null);

        $this->actingAs($customer)->get(route('pens.assign', $pen))->assertForbidden();

        Livewire::actingAs($customer)
            ->test(AssignBroodcocks::class, ['pen' => $pen])
            ->assertForbidden();

        $this->assertNull($bird->fresh()?->pen_id);
    }

    // -----------------------------------------------------------------
    // Staff - full access except deleting
    // -----------------------------------------------------------------

    public function test_staff_can_see_and_edit_pens(): void
    {
        $staff = User::factory()->staff()->create();
        $pen = Pen::factory()->create(['code' => 'P-01']);

        $this->actingAs($staff)->get(route('pens.index'))->assertOk();
        $this->actingAs($staff)->get(route('pens.show', $pen))->assertOk();
        $this->actingAs($staff)->get(route('pens.create'))->assertOk();
        $this->actingAs($staff)->get(route('pens.edit', $pen))->assertOk();
        $this->actingAs($staff)->get(route('pens.assign', $pen))->assertOk();
    }

    public function test_staff_cannot_delete_a_pen(): void
    {
        $staff = User::factory()->staff()->create();
        $pen = Pen::factory()->create(['code' => 'P-02']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->call('confirmDelete', $pen->id)
            ->assertForbidden();

        Livewire::actingAs($staff)
            ->test(Show::class, ['pen' => $pen])
            ->call('confirmDelete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($pen);
    }

    /** Hiding the button is a courtesy; the policy is the gate. */
    public function test_the_delete_button_is_not_shown_to_staff(): void
    {
        $staff = User::factory()->staff()->create();
        Pen::factory()->create(['code' => 'P-03']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertDontSee('Delete');
    }

    // -----------------------------------------------------------------
    // Owner - may delete
    // -----------------------------------------------------------------

    public function test_the_owner_can_delete_a_pen(): void
    {
        $owner = User::factory()->owner()->create();
        $pen = Pen::factory()->create(['code' => 'P-04']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirmDelete', $pen->id)
            ->call('delete');

        $this->assertSoftDeleted($pen);
    }

    // -----------------------------------------------------------------
    // Deactivated accounts
    // -----------------------------------------------------------------

    public function test_a_deactivated_staff_account_cannot_reach_the_pens_screens(): void
    {
        $staff = User::factory()->staff()->inactive()->create();
        $pen = Pen::factory()->create();

        $this->actingAs($staff)->get(route('pens.index'))->assertRedirect('/login');
        $this->assertNotSoftDeleted($pen);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function makeBroodcock(string $name, ?Pen $pen): Broodcock
    {
        return Broodcock::create([
            'name' => $name,
            'sex' => Sex::Male,
            'status' => BroodcockStatus::Active,
            'pen_id' => $pen?->id,
        ]);
    }

    private function registerPenRoutes(): void
    {
        if (Route::has('pens.index')) {
            return;
        }

        Route::middleware(['web', 'auth', 'active'])->group(function (): void {
            Route::livewire('/pens', Index::class)->name('pens.index');
            Route::livewire('/pens/create', Form::class)->name('pens.create');
            Route::livewire('/pens/{pen}/edit', Form::class)->name('pens.edit');
            Route::livewire('/pens/{pen}/birds', AssignBroodcocks::class)->name('pens.assign');
            Route::livewire('/pens/{pen}', Show::class)->name('pens.show');
        });

        // Routes added after boot are named after they are registered, so the
        // collection's name lookup has to be rebuilt for route() to find them.
        $this->app['router']->getRoutes()->refreshNameLookups();
    }
}
