<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Livewire\Profile\Edit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A user editing their OWN account.
 *
 * The security-relevant property here is that this screen has no id in it at
 * all: it always operates on the authenticated user. There is no parameter to
 * tamper with, which is why it is a separate component from Users\Form rather
 * than the same one with a different policy.
 */
final class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_own_details(): void
    {
        $user = User::factory()->create([
            'full_name' => 'Old Name',
            'position' => 'Keeper',
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('full_name', 'New Name')
            ->set('position', 'Head Keeper')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('New Name', $user->full_name);
        $this->assertSame('Head Keeper', $user->position);
    }

    /**
     * The screen shows role as read-only text. This asserts the component has no
     * way to change it even if the markup were tampered with — role escalation
     * from a self-service screen is the one thing that must be impossible here.
     */
    public function test_a_user_cannot_change_their_own_role_or_active_flag(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('full_name', 'Still Staff')
            ->call('save');

        $user->refresh();

        $this->assertSame('staff', $user->role->value);
        $this->assertTrue($user->is_active);
    }

    public function test_a_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        $this->assertTrue($user->hasProfilePhoto());
        Storage::disk('local')->assertExists($user->profile_photo_path);
    }

    /** A phone photo is routinely far over the limit, so the message matters. */
    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('huge.jpg')->size(5000))
            ->assertHasErrors(['photo' => 'max']);

        $this->assertFalse($user->fresh()->hasProfilePhoto());
    }

    public function test_a_non_image_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->create('notes.pdf', 100))
            ->assertHasErrors(['photo']);
    }

    /**
     * Replacing a photo must not orphan the old file. The new one is stored
     * first and the old deleted after, so a failed upload never loses the
     * existing photo.
     */
    public function test_replacing_a_photo_deletes_the_previous_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('first.jpg'))
            ->call('save');

        $first = $user->fresh()->profile_photo_path;

        $component->set('photo', UploadedFile::fake()->image('second.jpg'))->call('save');

        $second = $user->fresh()->profile_photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_a_user_can_remove_their_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('save');

        $path = $user->fresh()->profile_photo_path;

        $component->call('removePhoto');

        $this->assertFalse($user->fresh()->hasProfilePhoto());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_user_can_change_their_password_with_the_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'old-password')
            ->set('password', 'a-new-password')
            ->set('password_confirmation', 'a-new-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
    }

    public function test_the_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'not-my-password')
            ->set('password', 'a-new-password')
            ->set('password_confirmation', 'a-new-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_a_user_cannot_take_an_email_another_account_already_uses(): void
    {
        User::factory()->create(['email' => 'taken@ssguad.test']);
        $user = User::factory()->create(['email' => 'mine@ssguad.test']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('email', 'taken@ssguad.test')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    /** Keeping your own email must not trip the uniqueness rule against yourself. */
    public function test_a_user_can_save_without_changing_their_email(): void
    {
        $user = User::factory()->create(['email' => 'mine@ssguad.test']);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('full_name', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('mine@ssguad.test', $user->fresh()->email);
    }

    public function test_a_guest_cannot_reach_the_profile_screen(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }
}
