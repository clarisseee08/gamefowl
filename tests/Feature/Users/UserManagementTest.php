<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Livewire\Users\Form;
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User management is owner-only in full.
 *
 * The lock-yourself-out cases matter as much as the permission cases: a farm
 * system with no reachable administrator account is unrecoverable without
 * database access.
 */
final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    public function test_an_owner_can_view_the_user_list(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('users.index'))->assertOk();
    }

    public function test_a_record_keeper_cannot_view_the_user_list(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('users.index'))->assertForbidden();
    }

    public function test_a_customer_cannot_view_the_user_list(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('users.index'))->assertForbidden();
    }

    public function test_a_guest_cannot_view_the_user_list(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_a_record_keeper_cannot_reach_the_component_directly(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Creating
    // -----------------------------------------------------------------

    public function test_an_owner_can_create_an_account(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class)
            ->set('full_name', 'Maria Santos')
            ->set('email', 'maria@ssguad.test')
            ->set('role', UserRole::Staff->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'maria@ssguad.test')->first();

        $this->assertNotNull($created);
        $this->assertSame(UserRole::Staff, $created->role);
        $this->assertTrue($created->is_active);
        // The password must be hashed, never stored in the clear.
        $this->assertNotSame('secret-password', $created->password);
        $this->assertTrue(Hash::check('secret-password', $created->password));
    }

    public function test_a_record_keeper_cannot_create_an_account(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->assertForbidden();

        $this->assertSame(1, User::count());
    }

    public function test_passwords_must_match(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class)
            ->set('full_name', 'Mismatch')
            ->set('email', 'mismatch@ssguad.test')
            ->set('role', UserRole::Staff->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'different-password')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@ssguad.test']);
    }

    public function test_an_email_cannot_be_reused(): void
    {
        User::factory()->create(['email' => 'taken@ssguad.test']);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class)
            ->set('full_name', 'Duplicate')
            ->set('email', 'taken@ssguad.test')
            ->set('role', UserRole::Staff->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasErrors('email');
    }

    // -----------------------------------------------------------------
    // Editing
    // -----------------------------------------------------------------

    public function test_leaving_the_password_blank_on_edit_keeps_the_existing_one(): void
    {
        $target = User::factory()->staff()->create();
        $originalHash = $target->password;

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class, ['user' => $target])
            ->set('full_name', 'Renamed Person')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();

        $this->assertSame('Renamed Person', $target->full_name);
        $this->assertSame($originalHash, $target->password, 'A blank password box must not blank the password.');
    }

    public function test_an_owner_can_change_someone_elses_role(): void
    {
        $target = User::factory()->customer()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class, ['user' => $target])
            ->set('role', UserRole::Staff->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Staff, $target->fresh()->role);
    }

    // -----------------------------------------------------------------
    // Lock-out protection
    // -----------------------------------------------------------------

    public function test_an_owner_cannot_demote_themselves(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Form::class, ['user' => $owner])
            ->set('role', UserRole::Customer->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(UserRole::Owner, $owner->fresh()->role);
    }

    public function test_an_owner_cannot_deactivate_themselves_through_the_form(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Form::class, ['user' => $owner])
            ->set('is_active', false)
            ->call('save')
            ->assertHasErrors('is_active');

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_an_owner_cannot_deactivate_themselves_from_the_list(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirmToggle', $owner->id)
            ->assertForbidden();

        $this->assertTrue($owner->fresh()->is_active);
    }

    // -----------------------------------------------------------------
    // Deactivation
    // -----------------------------------------------------------------

    public function test_an_owner_can_deactivate_another_account(): void
    {
        $target = User::factory()->staff()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmToggle', $target->id)
            ->call('toggleActive');

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_deactivating_keeps_the_account_rather_than_deleting_it(): void
    {
        $target = User::factory()->staff()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmToggle', $target->id)
            ->call('toggleActive');

        // The row must survive so their name still resolves on every record
        // they created - that is the whole point of the audit trail.
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertNotSoftDeleted($target);
    }

    public function test_a_reactivated_account_can_sign_in_again(): void
    {
        $target = User::factory()->staff()->inactive()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmToggle', $target->id)
            ->call('toggleActive');

        $this->assertTrue($target->fresh()->is_active);

        // The owner is still signed in from the actions above; sign out first
        // or the login below has nothing to prove.
        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($target->fresh());
    }
}
