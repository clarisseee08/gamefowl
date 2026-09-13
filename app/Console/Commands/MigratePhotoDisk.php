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
 * Moves photo FILES onto another disk and repoints their rows at it.
 *
 * THE PROBLEM THIS EXISTS FOR. `broodcock_photos.disk` is per-row, deliberately
 * - it is what lets an old photo still resolve after the default disk changes.
 * Combined with one database shared between every environment, it has a sharp
 * edge: a photo uploaded from a developer's machine is written to that
 * machine's local disk and the row records `public`. The deployed site reads
 * the SAME row, looks in its own empty `storage/app/public`, and serves a 404.
 * The photo is not lost; it is simply somewhere the server cannot reach.
 *
 * Nothing about that is visible from the application. The row is valid, the
 * file exists, and the page shows a broken image.
 *
 * SAFETY, in the order it matters:
 *
 *  1. The source file is NEVER deleted. The worst outcome is a duplicate.
 *  2. The copy is verified on the target disk BEFORE the row is repointed, so
 *     a failed upload leaves the row pointing at a file that still works.
 *  3. Idempotent - rows already on the target are skipped, so a second run
 *     costs one existence check each and changes nothing.
 *  4. --dry-run shows the plan without writing a byte.
 *
 * Run it from a machine that can reach BOTH disks - which, for the local-to-
 * bucket direction, means a developer's machine with the SUPABASE_S3_*
 * credentials, not the server:
 *
 *     php artisan photos:migrate-disk --to=supabase --dry-run
 *     php artisan photos:migrate-disk --to=supabase
 */
final class MigratePhotoDisk extends Command
{
    protected $signature = 'photos:migrate-disk
                            {--to= : The disk to move photos onto (default: config gfms.photo_disk)}
                            {--dry-run : Show what would happen and write nothing}';

    protected $description = 'Copy photo files onto another disk and repoint their rows at it';

    public function handle(): int
    {
        $target = (string) ($this->option('to') ?: StorePhoto::disk());
        $dryRun = (bool) $this->option('dry-run');

        if (! array_key_exists($target, config('filesystems.disks', []))) {
            $this->components->error("There is no disk called [{$target}].");

            return self::FAILURE;
        }

        $stale = BroodcockPhoto::query()->where('disk', '!=', $target)->get();

        if ($stale->isEmpty()) {
            $this->components->info("Every photo is already on [{$target}]. Nothing to do.");

            return self::SUCCESS;
        }

        $this->components->info(
            ($dryRun ? 'DRY RUN - ' : '')."Moving {$stale->count()} photo(s) onto [{$target}]."
        );
        $this->newLine();

        $moved = $skipped = $failed = 0;

        foreach ($stale as $photo) {
            $result = $this->move($photo, $target, $dryRun);

            match ($result) {
                'moved' => $moved++,
                'skipped' => $skipped++,
                default => $failed++,
            };
        }

        $this->newLine();
        $this->components->twoColumnDetail($dryRun ? 'Would move' : 'Moved', (string) $moved);
        $this->components->twoColumnDetail('Skipped (source missing)', (string) $skipped);
        $this->components->twoColumnDetail('Failed', (string) $failed);

        if (! $dryRun && $moved > 0) {
            $this->newLine();
            $this->components->info(
                'The original files were left where they were. Delete them by hand once you are satisfied.'
            );
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function move(BroodcockPhoto $photo, string $target, bool $dryRun): string
    {
        $label = "photo #{$photo->id} [{$photo->disk}] {$photo->path}";

        try {
            $source = Storage::disk($photo->disk);

            if (! $source->exists($photo->path)) {
                // Not a failure worth an exit code: the file was already gone,
                // and moving it is not this command's job to fix.
                $this->components->warn("{$label} - source file is missing, left alone");

                return 'skipped';
            }

            if ($dryRun) {
                $this->line("  would move  {$label} -> [{$target}]");

                return 'moved';
            }

            $bytes = $source->get($photo->path);
            Storage::disk($target)->put($photo->path, $bytes);

            // Verified BEFORE the row is repointed. If the upload silently did
            // nothing, the row keeps pointing at the file that still works.
            if (! Storage::disk($target)->exists($photo->path)) {
                $this->components->error("{$label} - copy did not land on [{$target}], row left unchanged");

                return 'failed';
            }

            // Best effort, and after the copy: a photo without a thumbnail
            // still displays, it just costs more to send.
            StorePhoto::writeThumbnail($target, $photo->path);

            $photo->update(['disk' => $target]);

            $this->line("  moved       {$label} -> [{$target}]".
                (Storage::disk($target)->exists(Thumbnail::pathFor($photo->path)) ? ' (+ thumbnail)' : ''));

            return 'moved';
        } catch (Throwable $e) {
            $this->components->error("{$label} - {$e->getMessage()}");

            return 'failed';
        }
    }
}
