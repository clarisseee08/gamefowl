<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Form;
use App\Livewire\Broodcocks\Show;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * One test per role per protected action.
 *
 * This is the cheapest and most direct evidence for the ISO 25010 security
 * characteristic, and it is what proves authorization is enforced server-side
 * rather than by hiding buttons in Blade.
 */
final class BroodcockAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Viewing - all three roles may browse the catalogue
    // ---------------------------------------------------------------

    public static function allRoles(): array
    {
        return [
            'owner' => ['owner'],
            'staff' => ['staff'],
            'customer' => ['customer'],
        ];
    }

    #[DataProvider('allRoles')]
    public function test_every_role_can_view_the_broodcock_list(string $role): void
    {
        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)->get(route('broodcocks.index'))->assertOk();
    }

    #[DataProvider('allRoles')]
    public function test_every_role_can_view_a_broodcock(string $role): void
    {
        $user = User::factory()->{$role}()->create();
        $bird = Broodcock::factory()->create();

        $this->actingAs($user)->get(route('broodcocks.show', $bird))->assertOk();
    }

    public function test_a_guest_cannot_view_broodcocks(): void
    {
        $bird = Broodcock::factory()->create();

        $this->get(route('broodcocks.index'))->assertRedirect(route('login'));
        $this->get(route('broodcocks.show', $bird))->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // Creating - internal staff only
    // ---------------------------------------------------------------

    public function test_an_owner_can_open_the_create_form(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('broodcocks.create'))
            ->assertOk();
    }

    public function test_a_record_keeper_can_open_the_create_form(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('broodcocks.create'))
            ->assertOk();
    }

    public function test_a_customer_cannot_open_the_create_form(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('broodcocks.create'))
            ->assertForbidden();
    }

    public function test_a_customer_cannot_create_a_broodcock_through_the_component(): void
    {
        $this->actingAs(User::factory()->customer()->create());

        Livewire::test(Form::class)->assertForbidden();

        $this->assertDatabaseCount('broodcocks', 0);
    }

    // ---------------------------------------------------------------
    // Updating - internal staff only
    // ---------------------------------------------------------------

    public function test_a_record_keeper_can_update_a_broodcock(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Original']);

        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(Form::class, ['broodcock' => $bird])
            ->set('name', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed', $bird->fresh()->name);
    }

    public function test_a_customer_cannot_update_a_broodcock(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Original']);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('broodcocks.edit', $bird))
            ->assertForbidden();

        $this->assertSame('Original', $bird->fresh()->name);
    }

    // ---------------------------------------------------------------
    // Deleting - OWNER ONLY. This is the important one.
    // ---------------------------------------------------------------

    public function test_an_owner_can_delete_a_broodcock(): void
    {
        $bird = Broodcock::factory()->create();

        $this->actingAs(User::factory()->owner()->create());

        Livewire::test(Show::class, ['broodcock' => $bird])
            ->call('delete');

        $this->assertSoftDeleted($bird);
    }

    /**
     * The spec calls this out explicitly: a record keeper must genuinely be
     * unable to reach a delete action, not merely have the button hidden.
     * This drives the component method directly, bypassing the UI entirely.
     */
    public function test_a_record_keeper_cannot_delete_a_broodcock_even_by_calling_the_action_directly(): void
    {
        $bird = Broodcock::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(Show::class, ['broodcock' => $bird])
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($bird);
    }

    public function test_a_customer_cannot_delete_a_broodcock(): void
    {
        $bird = Broodcock::factory()->create();

        $this->actingAs(User::factory()->customer()->create());

        Livewire::test(Show::class, ['broodcock' => $bird])
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($bird);
    }

    public function test_a_record_keeper_cannot_reach_the_delete_confirmation(): void
    {
        $bird = Broodcock::factory()->create();

        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(Show::class, ['broodcock' => $bird])
            ->call('confirmDeletion')
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Data hiding - customers must not see internal remarks
    // ---------------------------------------------------------------

    public function test_a_customer_does_not_see_internal_notes(): void
    {
        $bird = Broodcock::factory()->create([
            'name' => 'Bruno',
            'notes' => 'SECRET-INTERNAL-COST-DATA',
        ]);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('broodcocks.show', $bird))
            ->assertOk()
            ->assertSee('Bruno')
            ->assertDontSee('SECRET-INTERNAL-COST-DATA');
    }

    public function test_internal_staff_do_see_internal_notes(): void
    {
        $bird = Broodcock::factory()->create(['notes' => 'SECRET-INTERNAL-COST-DATA']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('broodcocks.show', $bird))
            ->assertOk()
            ->assertSee('SECRET-INTERNAL-COST-DATA');
    }

    public function test_a_deactivated_user_cannot_reach_broodcocks(): void
    {
        $user = User::factory()->owner()->inactive()->create();

        $this->actingAs($user)
            ->get(route('broodcocks.index'))
            ->assertRedirect(route('login'));
    }
}
