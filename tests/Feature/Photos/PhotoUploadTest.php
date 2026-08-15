<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Actions\Photos\DeletePhoto;
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

final class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never the real Supabase bucket from a test run.
        Storage::fake($this->disk());

        $this->definePhotoRoutes();
    }

    // -----------------------------------------------------------------
    // Storing
    // -----------------------------------------------------------------

    public function test_staff_can_upload_a_photo_of_a_broodcock(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('rooster.jpg')])
            ->set('caption', 'Side view after conditioning')
            ->call('save')
            ->assertHasNoErrors();

        $photo = BroodcockPhoto::query()->sole();

        $this->assertSame($bird->id, $photo->broodcock_id);
        $this->assertSame('Side view after conditioning', $photo->caption);
        $this->assertSame($this->disk(), $photo->disk);

        Storage::disk($this->disk())->assertExists($photo->path);
    }

    public function test_the_uploader_is_taken_from_the_signed_in_user_not_from_the_request(): void
    {
        $staff = User::factory()->staff()->create();
        $someoneElse = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            // A crafted request cannot name a different uploader: the component
            // has no uploaded_by property to set in the first place.
            ->set('photos', [UploadedFile::fake()->image('rooster.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($staff->id, BroodcockPhoto::query()->sole()->uploaded_by);
        $this->assertSame(0, $someoneElse->uploadedPhotos()->count());
    }

    public function test_the_stored_path_uses_a_per_bird_prefix_and_never_the_uploaded_filename(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('../../etc/passwd.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $path = BroodcockPhoto::query()->sole()->path;

        $this->assertStringStartsWith("broodcocks/{$bird->id}/", $path);
        $this->assertStringNotContainsString('passwd', $path);
        $this->assertStringNotContainsString('..', $path);
        $this->assertMatchesRegularExpression(
            '#^broodcocks/\d+/[0-9a-f-]{36}\.(jpg|jpeg|png|webp)$#',
            $path,
        );
    }

    public function test_several_photos_can_be_uploaded_at_once(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.png'),
                UploadedFile::fake()->image('three.webp'),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, $bird->photos()->count());

        foreach (BroodcockPhoto::all() as $photo) {
            Storage::disk($this->disk())->assertExists($photo->path);
        }
    }

    public function test_the_first_photo_of_a_bird_becomes_its_main_photo_automatically(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('first.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(BroodcockPhoto::query()->sole()->is_primary);

        // The second one must not steal it.
        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('second.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $bird->photos()->where('is_primary', true)->count());
    }

    // -----------------------------------------------------------------
    // Server-side validation - the client is never trusted
    // -----------------------------------------------------------------

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->create('accounts.pdf', 40, 'application/pdf')])
            ->call('save')
            ->assertHasErrors('photos.0');

        $this->assertSame(0, BroodcockPhoto::query()->count());
    }

    public function test_a_disallowed_image_type_is_rejected(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        // A real image, but not one of the accepted types.
        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('animation.gif')])
            ->call('save')
            ->assertHasErrors('photos.0');

        $this->assertSame(0, BroodcockPhoto::query()->count());
    }

    public function test_a_photo_over_the_size_limit_is_rejected(): void
    {
        config(['gfms.photos.max_kilobytes' => 1024]);

        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('huge.jpg')->size(4096)])
            ->call('save')
            ->assertHasErrors('photos.0');

        $this->assertSame(0, BroodcockPhoto::query()->count());
    }

    public function test_a_bird_cannot_exceed_the_configured_photo_limit(): void
    {
        config(['gfms.photos.max_per_broodcock' => 2]);

        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        BroodcockPhoto::factory()->count(2)->forBroodcock($bird)->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('one-too-many.jpg')])
            ->call('save')
            ->assertHasErrors('photos');

        $this->assertSame(2, $bird->photos()->count());
    }

    // -----------------------------------------------------------------
    // Removing a pending file before it is saved
    // -----------------------------------------------------------------

    public function test_a_chosen_photo_can_be_dropped_before_saving(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [
                UploadedFile::fake()->image('keep.jpg'),
                UploadedFile::fake()->image('drop.jpg'),
            ])
            ->call('removePending', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $bird->photos()->count());
    }

    // -----------------------------------------------------------------
    // Deleting
    // -----------------------------------------------------------------

    public function test_deleting_a_photo_removes_the_row_and_the_file(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Upload::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('gone.jpg')])
            ->call('save');

        $photo = BroodcockPhoto::query()->sole();
        Storage::disk($this->disk())->assertExists($photo->path);

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('delete', $photo->id);

        $this->assertDatabaseMissing('broodcock_photos', ['id' => $photo->id]);
        Storage::disk($this->disk())->assertMissing($photo->path);
    }

    public function test_a_storage_failure_does_not_resurrect_the_deleted_row(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create([
            // Nothing was ever written here, so the disk delete is a no-op at
            // best and an error at worst - either way the row must stay gone.
            'path' => 'broodcocks/'.$bird->id.'/never-written.jpg',
        ]);

        app(DeletePhoto::class)->handle($photo);

        $this->assertDatabaseMissing('broodcock_photos', ['id' => $photo->id]);
    }

    public function test_deleting_the_main_photo_promotes_another_one(): void
    {
        $bird = Broodcock::factory()->create();
        $primary = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $other = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        app(DeletePhoto::class)->handle($primary);

        $this->assertTrue($other->fresh()->is_primary);
        $this->assertSame(1, $bird->photos()->where('is_primary', true)->count());
    }

    // -----------------------------------------------------------------
    // Captions
    // -----------------------------------------------------------------

    public function test_a_caption_can_be_edited_inline(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->withCaption('Old text')->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('startEditingCaption', $photo->id)
            ->set('captionDraft', 'Front view, 18 months')
            ->call('saveCaption')
            ->assertHasNoErrors();

        $this->assertSame('Front view, 18 months', $photo->fresh()->caption);
    }

    public function test_an_over_long_caption_is_rejected(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->withCaption('Old text')->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('startEditingCaption', $photo->id)
            ->set('captionDraft', str_repeat('a', 300))
            ->call('saveCaption')
            ->assertHasErrors('captionDraft');

        $this->assertSame('Old text', $photo->fresh()->caption);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function disk(): string
    {
        return (string) config('gfms.photo_disk');
    }

    /**
     * The photo route lives in routes/web.php, which this module does not own.
     * Registering it here only when it is missing keeps these tests runnable on
     * their own and makes them use the real route as soon as it lands.
     */
    private function definePhotoRoutes(): void
    {
        if (Route::has('photos.show')) {
            return;
        }

        Route::middleware(['web', 'auth', 'active'])
            ->get('/photos/{photo}', [BroodcockPhotoController::class, 'show'])
            ->name('photos.show');

        Route::getRoutes()->refreshNameLookups();
    }
}
