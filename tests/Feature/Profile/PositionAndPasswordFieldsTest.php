<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Enums\UserRole;
use App\Livewire\Profile\Edit;
use App\Livewire\Users\Form;
use App\Models\User;
use App\Support\FarmPositions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Position is the console's dropdown, with Other opening a text box; and every
 * password field has the eye that shows and hides it.
 */
final class PositionAndPasswordFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_offers_the_farm_positions_and_other(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Edit::class)
            ->assertSeeInOrder([...FarmPositions::all(), 'Other (type your own)'])
            ->assertSee('id="position_other"', false);
    }

    public function test_a_listed_position_is_saved_as_chosen(): void
    {
        $user = User::factory()->staff()->create(['position' => null]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('position_choice', 'Caretaker')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Caretaker', $user->fresh()->position);
    }

    public function test_other_saves_what_was_typed(): void
    {
        $user = User::factory()->staff()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('position_choice', FarmPositions::OTHER)
            ->set('position_other', '  Gamecock Handler ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Gamecock Handler', $user->fresh()->position);
    }

    public function test_a_position_typed_before_the_list_opens_as_other(): void
    {
        $user = User::factory()->staff()->create(['position' => 'Head Keeper']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->assertSet('position_choice', FarmPositions::OTHER)
            ->assertSet('position_other', 'Head Keeper');
    }

    public function test_a_listed_position_opens_as_itself(): void
    {
        Livewire::actingAs(User::factory()->staff()->create(['position' => 'Breeder']))
            ->test(Edit::class)
            ->assertSet('position_choice', 'Breeder')
            ->assertSet('position_other', '');
    }

    public function test_choosing_no_position_clears_it(): void
    {
        $user = User::factory()->staff()->create(['position' => 'Breeder']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('position_choice', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->position);
    }

    public function test_the_owner_sets_a_new_accounts_position_the_same_way(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class)
            ->assertSee('Other (type your own)')
            ->set('full_name', 'Maria Santos')
            ->set('email', 'maria@ssguad.test')
            ->set('role', UserRole::Staff->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->set('position_choice', 'Trainer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Trainer', User::query()->where('email', 'maria@ssguad.test')->value('position'));
    }

    public function test_every_password_field_on_the_profile_has_the_eye(): void
    {
        $html = Livewire::actingAs(User::factory()->staff()->create())->test(Edit::class)->html();

        foreach (['current_password', 'password', 'password_confirmation'] as $field) {
            $this->assertStringContainsString('data-reveals="'.$field.'"', $html);
        }
        $this->assertSame(3, substr_count($html, 'aria-label="Show password"'));
    }

    public function test_the_password_reset_page_has_the_eye_on_both_fields(): void
    {
        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'keeper@ssguad.test']))
            ->assertOk()
            ->assertSee('data-reveals="password"', false)
            ->assertSee('data-reveals="password_confirmation"', false);
    }

    public function test_the_live_password_checks_are_on_the_page(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Edit::class)
            ->assertSee('At least 8 characters.')
            ->assertSee('The passwords match.', false);
    }
}
