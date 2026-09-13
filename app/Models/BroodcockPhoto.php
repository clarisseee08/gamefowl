<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\RecordCache;
use Database\Factories\BroodcockPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BroodcockPhoto extends Model
{
    /** @use HasFactory<BroodcockPhotoFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'broodcock_id',
        'path',
        'disk',
        'caption',
        'is_primary',
        'uploaded_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * A photo change alters the catalogue grid, so it invalidates the same
     * cache a change to the bird itself does.
     *
     * Setting a different primary photo does not touch the broodcocks table at
     * all - it writes is_primary here - so without this the grid would keep
     * serving the old picture until the cache aged out on its own.
     *
     * No `restored`: this model is not soft-deleted.
     */
    protected static function booted(): void
    {
        $forget = static function (): void {
            RecordCache::invalidate();
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['broodcock_id', 'path', 'caption', 'is_primary'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('photo');
    }

    /**
     * A URL the browser can render.
     *
     * The Supabase bucket is private, so signed temporary URLs are used where
     * the driver supports them, falling back to a plain URL for the local
     * public disk.
     */
    public function url(int $minutes = 30): ?string
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($this->path)) {
            return null;
        }

        if ($disk->providesTemporaryUrls()) {
            return $disk->temporaryUrl($this->path, now()->addMinutes($minutes));
        }

        return $disk->url($this->path);
    }

    /** @return BelongsTo<Broodcock, $this> */
    public function broodcock(): BelongsTo
    {
        return $this->belongsTo(Broodcock::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
