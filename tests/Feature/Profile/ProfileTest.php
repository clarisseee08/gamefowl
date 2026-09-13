<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Actions\Photos\StorePhoto;
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

    /**
     * The disk profile photos are written to.
     *
     * Read rather than hardcoded, because which disk that IS is the thing under
     * test - see test_a_profile_photo_is_written_to_the_configured_photo_disk().
     * Hardcoding 'local' here is what let the two disks drift apart unnoticed.
     */
    private function photoDisk(): string
    {
        return StorePhoto::disk();
    }

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
        Storage::fake($this->photoDisk());
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->profile_photo_path);
        $this->assertTrue($user->hasProfilePhoto());
        Storage::disk($this->photoDisk())->assertExists($user->profile_photo_path);
    }

    /**
     * Profile photos go where BIRD photos go, and the two must not drift.
     *
     * This is a production bug that shipped: the component read
     * config('filesystems.default') while everything else read
     * config('gfms.photo_disk'). Render sets GFMS_PHOTO_DISK=supabase and never
     * sets FILESYSTEM_DISK, so profile photos were written to the container's
     * own filesystem - which is wiped on every restart. Nothing failed; the
     * photos simply disappeared, and only profile photos did.
     *
     * Asserting the disk NAME on the row is what catches it: the two configs
     * are identical in the test environment unless you look at which one was
     * actually consulted.
     */
    public function test_a_profile_photo_is_written_to_the_configured_photo_disk(): void
    {
        // Deliberately different from gfms.photo_disk, so reading the wrong key
        // produces the wrong answer rather than accidentally the right one.
        config(['gfms.photo_disk' => 'public', 'filesystems.default' => 'local']);

        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('public', $user->profile_photo_disk);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        Storage::disk('local')->assertMissing($user->profile_photo_path);
    }

    /**
     * An existing row keeps resolving against the disk it was WRITTEN to, not
     * whatever the config says today. That is the whole reason the disk is a
     * column rather than a lookup, and it is what stops this fix from orphaning
     * every photo uploaded before it.
     */
    public function test_an_existing_photo_still_resolves_after_the_disk_changes(): void
    {
        config(['gfms.photo_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('old.jpg'))
            ->call('save');

        $user->refresh();
        $this->assertSame('local', $user->profile_photo_disk);

        // The farm switches to the bucket. The old photo must not vanish.
        config(['gfms.photo_disk' => 'public']);

        $this->assertNotNull($user->fresh()->profilePhotoUrl());
    }

    /** A phone photo is routinely far over the limit, so the message matters. */
    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake($this->photoDisk());
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('huge.jpg')->size(5000))
            ->assertHasErrors(['photo' => 'max']);

        $this->assertFalse($user->fresh()->hasProfilePhoto());
    }

    public function test_a_non_image_is_rejected(): void
    {
        Storage::fake($this->photoDisk());
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
        Storage::fake($this->photoDisk());
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('first.jpg'))
            ->call('save');

        $first = $user->fresh()->profile_photo_path;

        $component->set('photo', UploadedFile::fake()->image('second.jpg'))->call('save');

        $second = $user->fresh()->profile_photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk($this->photoDisk())->assertMissing($first);
        Storage::disk($this->photoDisk())->assertExists($second);
    }

    public function test_a_user_can_remove_their_photo(): void
    {
        Storage::fake($this->photoDisk());
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('save');

        $path = $user->fresh()->profile_photo_path;

        $component->call('removePhoto');

        $this->assertFalse($user->fresh()->hasProfilePhoto());
        Storage::disk($this->photoDisk())->assertMissing($path);
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
