<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Livewire\Users\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The role descriptions on Add a user speak of archiving, not deleting.
 *
 * Nothing in the system is ever deleted - an archived record is hidden and the
 * owner can restore it - and a thesis reviewer caught the old "deleting
 * records" copy contradicting that. Staff may archive a photo and nothing else.
 */
final class RoleDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_role_description_speaks_of_deleting(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertStringNotContainsStringIgnoringCase('delet', $role->description(), $role->value);
        }
    }

    public function test_the_add_user_form_describes_roles_in_terms_of_archiving(): void
    {
        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class)
            ->assertSee('Full access, including archiving and restoring records and managing user accounts.')
            ->assertSee('Can add and edit records and archive photos, but cannot archive other records or manage users.');
    }
}
