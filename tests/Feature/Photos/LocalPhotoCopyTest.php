<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Support\LocalPhotoCopy;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The web server's own copy of each photo it serves, so a photo is fetched
 * from the bucket in Tokyo once rather than on every view.
 */
final class LocalPhotoCopyTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('bucket');
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gfms-photo-copies-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_the_first_view_downloads_the_photo_and_keeps_a_copy(): void
    {
        Storage::disk('bucket')->put('broodcocks/1/a.jpg', 'photo-bytes');

        $copies = new LocalPhotoCopy($this->directory);

        $this->assertSame('photo-bytes', $this->read($copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/a.jpg')));
        $this->assertFileExists($copies->copyPathFor('bucket', 'broodcocks/1/a.jpg'));
    }

    /** The point of the copy: the second view does not go back to the bucket. */
    public function test_later_views_are_served_from_the_copy(): void
    {
        Storage::disk('bucket')->put('broodcocks/1/a.jpg', 'photo-bytes');
        $copies = new LocalPhotoCopy($this->directory);
        $this->read($copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/a.jpg'));

        Storage::disk('bucket')->delete('broodcocks/1/a.jpg');

        $this->assertSame('photo-bytes', $this->read($copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/a.jpg')));
    }

    public function test_a_file_the_bucket_does_not_have_is_null(): void
    {
        $copies = new LocalPhotoCopy($this->directory);

        $this->assertNull($copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/missing.jpg'));
    }

    /**
     * Older photos have no thumbnail. Asking the bucket again on every view
     * only to be told no is the cost the copy exists to remove, so the answer
     * is remembered (for a day, so a later backfill is still found).
     */
    public function test_a_missing_file_is_remembered_rather_than_asked_for_again(): void
    {
        $copies = new LocalPhotoCopy($this->directory);
        $copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/a-thumb.jpg');

        Storage::disk('bucket')->put('broodcocks/1/a-thumb.jpg', 'thumb-bytes');

        $this->assertNull($copies->open(Storage::disk('bucket'), 'bucket', 'broodcocks/1/a-thumb.jpg'));
    }

    public function test_each_disk_and_path_has_its_own_copy(): void
    {
        $copies = new LocalPhotoCopy($this->directory);

        $this->assertNotSame($copies->copyPathFor('bucket', 'a.jpg'), $copies->copyPathFor('bucket', 'b.jpg'));
        $this->assertNotSame($copies->copyPathFor('bucket', 'a.jpg'), $copies->copyPathFor('other', 'a.jpg'));
        $this->assertStringEndsWith('.jpg', $copies->copyPathFor('bucket', 'a.jpg'));
    }

    /** @param  resource|null  $stream */
    private function read(mixed $stream): string
    {
        $this->assertIsResource($stream);
        $contents = (string) stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }
}
