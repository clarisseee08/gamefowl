<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\InvalidatesRecordCache;
use App\Support\RecordCache;
use Database\Factories\BroodcockPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BroodcockPhoto extends Model
{
    /** @use HasFactory<BroodcockPhotoFactory> */
    use HasFactory;

    use InvalidatesRecordCache;
    use LogsActivity;

    // Archived, never erased: a removed photo keeps its row and file and can
    // be restored from the Archive.
    use SoftDeletes;

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

    /**
     * /photos/{photo}, resolved from the cache, with its bird.
     *
     * Every <img> on a page is a request that resolves its photo here and then
     * asks BroodcockPhotoPolicy::view(), which for a visitor reads the bird
     * too: two round trips to Tokyo per thumbnail, ahead of the picture
     * itself. The row and its bird change only through a write, which clears
     * RecordCache, so the policy still decides on current data.
     *
     * The bird is loaded whole and in advance: the policy reads it for every
     * visitor, and Model::shouldBeStrict() would refuse a lazy load.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null || ! ctype_digit((string) $value)) {
            return parent::resolveRouteBinding($value, $field);
        }

        return RecordCache::remember('photo:'.$value, fn (): ?self => static::query()->with('broodcock')->find((int) $value));
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
