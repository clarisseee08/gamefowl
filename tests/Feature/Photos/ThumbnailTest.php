<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Actions\Photos\DeletePhoto;
use App\Actions\Photos\StorePhoto;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use App\Support\Thumbnail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photos are resized once, at upload, and grids serve the small copy.
 *
 * THE PROBLEM. Every grid in this application rendered the ORIGINAL file into a
 * box a couple of hundred pixels wide. A phone photo is routinely 4 MB and the
 * catalogue shows twelve per page, each streamed out of Supabase in Tokyo
 * through php-fpm on a container with four workers - roughly 48 MB of transfer
 * to draw one page of thumbnails, and four concurrent image requests enough to
 * occupy every worker the site has.
 *
 * THE RULE THAT OUTRANKS ALL OF IT. A thumbnail that cannot be built must never
 * fail an upload, and a photo without one must still display. Photographing a
 * bird out at the pens on farm wifi is the expensive part; losing it because GD
 * disliked a progressive JPEG would be indefensible. Several tests below assert
 * that fallback rather than the happy path.
 */
final class ThumbnailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('The gd extension is not loaded.');
        }

        Storage::fake(StorePhoto::disk());
    }

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    private function upload(Broodcock $bird, UploadedFile $file): BroodcockPhoto
    {
        return app(StorePhoto::class)->handle($bird, $file, $this->staff());
    }

    // -----------------------------------------------------------------
    // Generation
    // -----------------------------------------------------------------

    public function test_uploading_a_photo_also_writes_a_thumbnail(): void
    {
        $bird = Broodcock::factory()->create();

        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 2000, 1500));

        Storage::disk($photo->disk)->assertExists($photo->path);
        Storage::disk($photo->disk)->assertExists(Thumbnail::pathFor($photo->path));
    }

    public function test_the_thumbnail_is_smaller_than_the_original(): void
    {
        $bird = Broodcock::factory()->create();

        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 2400, 1800));

        $disk = Storage::disk($photo->disk);

        $this->assertLessThan(
            strlen((string) $disk->get($photo->path)),
            strlen((string) $disk->get(Thumbnail::pathFor($photo->path))),
            'The thumbnail is not smaller than the original, so it buys nothing.'
        );
    }

    public function test_the_thumbnail_is_bounded_on_its_longest_edge(): void
    {
        $bird = Broodcock::factory()->create();

        $photo = $this->upload($bird, UploadedFile::fake()->image('tall.jpg', 900, 3000));

        $bytes = Storage::disk($photo->disk)->get(Thumbnail::pathFor($photo->path));
        [$width, $height] = getimagesizefromstring((string) $bytes);

        $this->assertSame(Thumbnail::MAX_EDGE, max($width, $height));
        // Aspect ratio preserved: 900x3000 scaled to a 400 long edge is 120x400.
        $this->assertSame(120, $width);
    }

    /** Re-encoding an already-small image would cost quality for nothing. */
    public function test_an_image_smaller_than_the_limit_is_not_enlarged(): void
    {
        $bird = Broodcock::factory()->create();

        $photo = $this->upload($bird, UploadedFile::fake()->image('small.jpg', 120, 90));

        $bytes = Storage::disk($photo->disk)->get(Thumbnail::pathFor($photo->path));
        [$width, $height] = getimagesizefromstring((string) $bytes);

        $this->assertSame(120, $width);
        $this->assertSame(90, $height);
    }

    /** Whatever came in, the thumbnail is a JPEG - see Thumbnail's rule 2. */
    public function test_a_png_produces_a_jpeg_thumbnail(): void
    {
        $bird = Broodcock::factory()->create();

        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.png', 1200, 900));

        $this->assertStringEndsWith('.png', $photo->path);

        $path = Thumbnail::pathFor($photo->path);
        $this->assertStringEndsWith('-thumb.jpg', $path);

        $info = getimagesizefromstring((string) Storage::disk($photo->disk)->get($path));
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);
    }

    // -----------------------------------------------------------------
    // Serving
    // -----------------------------------------------------------------

    public function test_the_photo_route_serves_the_thumbnail_when_asked(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 2000, 1500));

        $full = $this->actingAs($this->staff())
            ->get(route('photos.show', $photo))
            ->streamedContent();

        $thumb = $this->actingAs($this->staff())
            ->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->streamedContent();

        $this->assertNotSame($full, $thumb);
        $this->assertLessThan(strlen($full), strlen($thumb));
    }

    /**
     * The fallback that makes this change safe to deploy.
     *
     * Every photo uploaded before this feature existed has no thumbnail. They
     * must keep displaying, not 404, until the backfill command has run - and
     * even afterwards for any image GD declined to decode.
     */
    public function test_a_photo_with_no_thumbnail_still_serves_the_original(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 1200, 900));

        // Simulate a photo that predates thumbnails.
        Storage::disk($photo->disk)->delete(Thumbnail::pathFor($photo->path));

        $response = $this->actingAs($this->staff())
            ->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']));

        $response->assertOk();
        $this->assertSame(
            Storage::disk($photo->disk)->get($photo->path),
            $response->streamedContent()
        );
    }

    /** A thumbnail request is still an authorized request. */
    public function test_a_guest_cannot_fetch_a_thumbnail(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg'));

        $this->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->assertRedirect(route('login'));
    }

    public function test_the_thumbnail_is_served_as_a_jpeg_with_nosniff(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.png', 1200, 900));

        $this->actingAs($this->staff())
            ->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->assertOk()
            // The original is a PNG; the thumbnail is not, and mislabelling it
            // is how a browser is invited to guess.
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    // -----------------------------------------------------------------
    // Cleanup
    // -----------------------------------------------------------------

    public function test_deleting_a_photo_removes_its_thumbnail_too(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg'));

        $thumb = Thumbnail::pathFor($photo->path);
        Storage::disk($photo->disk)->assertExists($thumb);

        app(DeletePhoto::class)->handle($photo);

        // The thumbnail path is derived, not stored, so nothing would ever find
        // an orphan left behind here.
        Storage::disk($photo->disk)->assertMissing($thumb);
        Storage::disk($photo->disk)->assertMissing($photo->path);
    }

    // -----------------------------------------------------------------
    // Backfill
    // -----------------------------------------------------------------

    public function test_the_backfill_command_builds_missing_thumbnails(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 1600, 1200));

        $thumb = Thumbnail::pathFor($photo->path);
        Storage::disk($photo->disk)->delete($thumb);
        Storage::disk($photo->disk)->assertMissing($thumb);

        $this->artisan('photos:backfill-thumbnails')->assertSuccessful();

        Storage::disk($photo->disk)->assertExists($thumb);
    }

    /** Re-running it must be a no-op, not a re-encode of the whole library. */
    public function test_the_backfill_command_skips_photos_that_already_have_one(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg', 1600, 1200));

        $before = Storage::disk($photo->disk)->get(Thumbnail::pathFor($photo->path));

        $this->artisan('photos:backfill-thumbnails')
            ->expectsOutputToContain('Already present')
            ->assertSuccessful();

        $this->assertSame($before, Storage::disk($photo->disk)->get(Thumbnail::pathFor($photo->path)));
    }

    // -----------------------------------------------------------------
    // Failure must be survivable
    // -----------------------------------------------------------------

    /**
     * A file GD cannot decode must not take the upload with it.
     *
     * Validation rejects non-images long before this, so reaching the action
     * with undecodable bytes means something unusual - a truncated transfer, an
     * exotic encoder. The photo is still the farm's data and must be stored.
     */
    public function test_an_undecodable_image_still_uploads_without_a_thumbnail(): void
    {
        $bird = Broodcock::factory()->create();

        $broken = UploadedFile::fake()->createWithContent('cock.jpg', 'this is not an image');

        $photo = $this->upload($bird, $broken);

        Storage::disk($photo->disk)->assertExists($photo->path);
        Storage::disk($photo->disk)->assertMissing(Thumbnail::pathFor($photo->path));

        // And it still displays, by falling back to the original.
        $this->actingAs($this->staff())
            ->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->assertOk();
    }

    public function test_the_thumbnailer_returns_null_rather_than_throwing_on_rubbish(): void
    {
        $this->assertNull(Thumbnail::fromBytes(null));
        $this->assertNull(Thumbnail::fromBytes(''));
        $this->assertNull(Thumbnail::fromBytes('not an image at all'));
    }

    /**
     * A small file that decodes to an enormous bitmap is an OOM kill of the
     * php-fpm worker, not a catchable exception - so it has to be refused on
     * the header, before the decode.
     */
    public function test_an_implausibly_large_image_is_refused_before_decoding(): void
    {
        // 100 megapixels of header, no pixel data needed to make the point.
        $header = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 10000, 10000)."\x08\x02\x00\x00\x00";

        $this->assertNull(Thumbnail::fromBytes($header));
    }
}
