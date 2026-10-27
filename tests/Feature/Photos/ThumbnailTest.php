<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Actions\Photos\DeletePhoto;
use App\Actions\Photos\StorePhoto;
use App\Enums\BroodcockStatus;
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
        // Aspect ratio preserved: 900x3000 scaled to the long edge keeps 3:10.
        $this->assertSame((int) round(900 * Thumbnail::MAX_EDGE / 3000), $width);
    }

    /**
     * The downscale is sharpened, which is what keeps a thumbnail from looking
     * out of focus.
     *
     * Resampling averages neighbouring pixels and softens every edge. The
     * test draws a soft edge, makes a thumbnail, and compares it against the
     * same downscale done WITHOUT the sharpen. A thumbnail no crisper than a
     * plain resample means the kernel has stopped being applied.
     */
    public function test_the_thumbnail_is_sharper_than_a_plain_resample(): void
    {
        $source = imagecreatetruecolor(1600, 400);
        for ($x = 0; $x < 1600; $x++) {
            // Dark to light across a 40px ramp in the middle - a soft edge.
            $level = (int) max(40, min(215, 40 + ($x - 780) * (175 / 40)));
            $colour = imagecolorallocate($source, $level, $level, $level);
            imageline($source, $x, 0, $x, 399, $colour);
        }
        ob_start();
        imagepng($source);
        $bytes = (string) ob_get_clean();

        // The comparison goes through JPEG at the same quality the thumbnail
        // uses. A first version compared against an unencoded resample and
        // passed with the sharpen REMOVED: JPEG's own ringing steepens an edge,
        // so the test was measuring compression, not sharpening.
        $resampled = imagecreatetruecolor(800, 200);
        imagecopyresampled($resampled, $source, 0, 0, 0, 0, 800, 200, 1600, 400);
        ob_start();
        imagejpeg($resampled, null, 82);
        $plain = imagecreatefromstring((string) ob_get_clean());

        $thumb = imagecreatefromstring((string) Thumbnail::fromBytes($bytes));

        // What sharpening does to an edge is overshoot it: a little darker
        // just before, a little lighter just after. A plain resample never
        // leaves the range of the pixels it averaged, so the sharpened
        // thumbnail's range across the edge is wider. (Measuring the steepest
        // single step was tried first and cannot see it - on a straight ramp
        // the middle is unchanged and only the shoulders move.)
        $range = function ($image): int {
            $lo = 255;
            $hi = 0;
            for ($x = 300; $x < 500; $x++) {
                $v = imagecolorat($image, $x, 100) & 0xFF;
                $lo = min($lo, $v);
                $hi = max($hi, $v);
            }

            return $hi - $lo;
        };

        $this->assertGreaterThanOrEqual($range($plain) + 4, $range($thumb),
            'The thumbnail does not overshoot the edge - the sharpen is not being applied.');
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

    /**
     * A thumbnail request is still an authorized request.
     *
     * The size parameter must not become a way around the policy. It is the
     * kind of thing that would be easy to miss: the full-size route is checked,
     * the thumbnail is "just a smaller copy of the same picture", and the check
     * quietly applies to one and not the other.
     */
    public function test_the_thumbnail_route_is_authorized_the_same_way_as_the_original(): void
    {
        $hidden = Broodcock::factory()->create(['status' => BroodcockStatus::Deceased]);
        $photo = $this->upload($hidden, UploadedFile::fake()->image('cock.jpg'));

        $this->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->assertForbidden();

        $this->get(route('photos.show', $photo))
            ->assertForbidden();
    }

    /** And a guest may fetch one for a bird the catalogue does show. */
    public function test_a_guest_can_fetch_a_thumbnail_of_a_publicly_visible_bird(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = $this->upload($bird, UploadedFile::fake()->image('cock.jpg'));

        $this->get(route('photos.show', ['photo' => $photo, 'size' => 'thumb']))
            ->assertOk();
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
