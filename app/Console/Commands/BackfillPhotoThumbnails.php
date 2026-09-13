<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Photos\StorePhoto;
use App\Models\BroodcockPhoto;
use App\Support\Thumbnail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Writes the missing small copy for photos uploaded before thumbnails existed.
 *
 * Not strictly required - BroodcockPhotoController falls back to the original
 * whenever a thumbnail is absent, so nothing is broken without this. What it
 * buys is that the fallback stops happening: until a photo has its thumbnail,
 * every grid cell showing it still streams the full-size file, which is the
 * cost thumbnails exist to remove.
 *
 * Run it once after deploying, then forget it:
 *
 *     php artisan photos:backfill-thumbnails
 *
 * SAFE TO RE-RUN. Photos that already have a thumbnail are skipped without
 * being re-encoded, so a second run costs one HEAD request each and changes
 * nothing. Use --force to rebuild them anyway, e.g. after changing MAX_EDGE.
 */
final class BackfillPhotoThumbnails extends Command
{
    protected $signature = 'photos:backfill-thumbnails
                            {--force : Rebuild thumbnails that already exist}
                            {--chunk=50 : Rows to load per batch}';

    protected $description = 'Generate the small copy for broodcock photos that do not have one yet';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $total = BroodcockPhoto::query()->count();

        if ($total === 0) {
            $this->components->info('There are no photos on record.');

            return self::SUCCESS;
        }

        $this->components->info(
            $force
                ? "Rebuilding thumbnails for {$total} photos."
                : "Checking {$total} photos for missing thumbnails."
        );

        $built = $skipped = $failed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Chunked by id so the memory cost does not grow with the flock. Each
        // photo is decoded one at a time; on a 192 MB container, loading every
        // row first and then resizing would be the thing that OOMs.
        BroodcockPhoto::query()
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($photos) use ($force, &$built, &$skipped, &$failed, $bar): void {
                foreach ($photos as $photo) {
                    $bar->advance();

                    try {
                        if (! $force && $this->alreadyHasThumbnail($photo)) {
                            $skipped++;

                            continue;
                        }

                        // No local temp file to read from here, so this pulls
                        // the original back off the disk - the one case where
                        // that round trip is unavoidable.
                        StorePhoto::writeThumbnail($photo->disk, $photo->path)
                            ? $built++
                            : $failed++;
                    } catch (Throwable $e) {
                        $failed++;
                        $this->newLine();
                        $this->components->warn("Photo #{$photo->id}: {$e->getMessage()}");
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->components->twoColumnDetail('Thumbnails written', (string) $built);
        $this->components->twoColumnDetail('Already present', (string) $skipped);
        $this->components->twoColumnDetail('Could not be built', (string) $failed);

        if ($failed > 0) {
            // Not a failure exit code. A photo GD cannot decode still displays
            // perfectly through the fallback, so this is information rather
            // than something to fail a deploy over.
            $this->components->warn(
                "{$failed} photo(s) have no thumbnail and will be served full-size. This is not a fault."
            );
        }

        return self::SUCCESS;
    }

    private function alreadyHasThumbnail(BroodcockPhoto $photo): bool
    {
        try {
            return Storage::disk($photo->disk)->exists(Thumbnail::pathFor($photo->path));
        } catch (Throwable) {
            return false;
        }
    }
}
