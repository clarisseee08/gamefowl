<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Enums\BroodcockStatus;
use App\Enums\UserRole;
use App\Http\Controllers\BroodcockPhotoController;
use App\Livewire\Photos\Gallery;
use App\Livewire\Photos\Upload;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Uploading, editing and deleting photos is internal work; viewing is part of
 * the customer-facing catalogue. Every one of those is checked here per role,
 * asserting the outcome AND that the data did not change.
 */
final class PhotoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake((string) config('gfms.photo_disk'));

        $this->definePhotoRoutes();
    }

    // -----------------------------------------------------------------
    // Uploading
    // -----------------------------------------------------------------

    public function test_the_owner_can_upload_photos(): void
    {
        $this->assertUploadAllowedFor(User::factory()->owner()->create());
    }

    public function test_staff_can_upload_photos(): void
    {
        $this->assertUploadAllowedFor(User::factory()->staff()->create());
    }

    public function test_a_customer_cannot_upload_photos(): void
    {
        $this->assertUploadDeniedFor(User::factory()->customer()->create());
    }

    public function test_a_deactivated_staff_member_cannot_upload_photos(): void
    {
        $this->assertUploadDeniedFor(User::factory()->staff()->inactive()->create());
    }

    /**
     * mount() and save() run on different requests, so the check in mount() is
     * not enough on its own - a permission can be taken away in between.
     */
    public function test_a_user_who_loses_access_after_opening_the_form_cannot_still_save(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        $component = Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('bird.jpg')]);

        // The owner demotes them while the upload form is still open.
        $staff->update(['role' => UserRole::Customer]);

        $component->call('save')->assertForbidden();

        $this->assertSame(0, $bird->photos()->count());
    }

    // -----------------------------------------------------------------
    // Deleting
    // -----------------------------------------------------------------

    public function test_a_customer_cannot_delete_a_photo(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        Livewire::actingAs($customer)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('delete', $photo->id)
            ->assertForbidden();

        $this->assertDatabaseHas('broodcock_photos', ['id' => $photo->id]);
    }

    public function test_staff_can_delete_a_photo(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('delete', $photo->id);

        $this->assertDatabaseMissing('broodcock_photos', ['id' => $photo->id]);
    }

    // -----------------------------------------------------------------
    // Changing the main photo and the caption
    // -----------------------------------------------------------------

    public function test_a_customer_cannot_change_the_main_photo(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create();
        $primary = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $other = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        Livewire::actingAs($customer)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('setPrimary', $other->id)
            ->assertForbidden();

        $this->assertTrue($primary->fresh()->is_primary);
        $this->assertFalse($other->fresh()->is_primary);
    }

    public function test_a_customer_cannot_edit_a_caption(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->withCaption('Untouched')->create();

        Livewire::actingAs($customer)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('startEditingCaption', $photo->id)
            ->assertForbidden();

        $this->assertSame('Untouched', $photo->fresh()->caption);
    }

    // -----------------------------------------------------------------
    // Viewing - the gallery
    // -----------------------------------------------------------------

    public function test_a_customer_can_view_the_gallery(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create();
        BroodcockPhoto::factory()->forBroodcock($bird)->withCaption('Visible to buyers')->create();

        Livewire::actingAs($customer)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertSee('Visible to buyers')
            // The management buttons are a courtesy, not the gate - but they
            // should not be dangled in front of someone who cannot use them.
            ->assertDontSee('Delete photo')
            ->assertDontSee('Make this the main photo');
    }

    public function test_staff_see_the_management_buttons_in_the_gallery(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        BroodcockPhoto::factory()->forBroodcock($bird)->create();
        BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertSee('Delete photo')
            ->assertSee('Make this the main photo');
    }

    // -----------------------------------------------------------------
    // Viewing - the private file itself
    // -----------------------------------------------------------------

    public function test_a_signed_in_customer_can_load_a_photo_file(): void
    {
        $photo = $this->photoWithFile();

        $response = $this->actingAs(User::factory()->customer()->create())
            ->get(route('photos.show', $photo));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /**
     * A guest may load a photo of a bird the catalogue would show them.
     *
     * This previously redirected to login. The catalogue is public now, and a
     * public catalogue whose images 302 to a sign-in form is a page of broken
     * thumbnails.
     */
    public function test_a_guest_can_load_a_photo_of_a_publicly_visible_bird(): void
    {
        $photo = $this->photoWithFile();

        $this->get(route('photos.show', $photo))->assertOk();
    }

    /**
     * But only of such a bird. Photo ids are sequential, so without this the
     * public could page through every photograph the farm has ever taken -
     * including of birds that died, which the catalogue never lists.
     */
    public function test_a_guest_cannot_load_a_photo_of_a_bird_the_catalogue_hides(): void
    {
        $photo = $this->photoWithFile();

        $photo->broodcock->update(['status' => BroodcockStatus::Deceased]);

        $this->get(route('photos.show', $photo))->assertForbidden();
    }

    public function test_a_deactivated_user_cannot_load_a_photo_file(): void
    {
        $photo = $this->photoWithFile();

        $this->actingAs(User::factory()->staff()->inactive()->create())
            ->get(route('photos.show', $photo))
            ->assertRedirect(route('login'));
    }

    public function test_a_missing_file_returns_a_not_found_rather_than_an_error(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create([
            'path' => 'broodcocks/'.$bird->id.'/does-not-exist.jpg',
        ]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('photos.show', $photo))
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function assertUploadAllowedFor(User $user): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($user)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('bird.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $bird->photos()->count());
    }

    private function assertUploadDeniedFor(User $user): void
    {
        $bird = Broodcock::factory()->create();

        // The Policy is consulted in mount(), so the screen never even renders.
        Livewire::actingAs($user)
            ->test(Upload::class, ['broodcock' => $bird])
            ->assertForbidden();

        $this->assertSame(0, $bird->photos()->count());
    }

    private function photoWithFile(): BroodcockPhoto
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        Storage::disk($photo->disk)->put($photo->path, 'not-really-a-jpeg-but-bytes-all-the-same');

        return $photo;
    }

    private function definePhotoRoutes(): void
    {
        if (Route::has('photos.show')) {
            return;
        }

        Route::middleware(['web', 'auth', 'active'])
            ->get('/photos/{photo}', [BroodcockPhotoController::class, 'show'])
            ->name('photos.show');

        // Routes added after the app has booted are not in the name lookup
        // table until it is rebuilt, so route() would not find this one.
        Route::getRoutes()->refreshNameLookups();
    }
}
