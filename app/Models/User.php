<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'role',
        'contact_number',
        'address',
        'position',
        'is_active',
        'profile_photo_path',
        'profile_photo_disk',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Never log the password hash, even though it is a real change.
            ->logOnly(['full_name', 'email', 'role', 'contact_number', 'address', 'position', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    // -----------------------------------------------------------------
    // Roles
    // -----------------------------------------------------------------

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /** Owner or staff - i.e. someone who works at the farm. */
    public function isInternal(): bool
    {
        return $this->role->isInternal();
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /** @return HasMany<BroodcockPhoto, $this> */
    public function uploadedPhotos(): HasMany
    {
        return $this->hasMany(BroodcockPhoto::class, 'uploaded_by');
    }

    /** @return HasMany<HealthRecord, $this> */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class, 'recorded_by');
    }

    /** @return HasMany<Report, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** @param Builder<User> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<User> $query */
    public function scopeRole(Builder $query, UserRole $role): void
    {
        $query->where('role', $role);
    }

    /** @param Builder<User> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            // whereLike(caseSensitive: false) compiles to ILIKE on Postgres and
            // to LIKE on SQLite, so the same scope works in production and in
            // the in-memory test database.
            $q->whereLike('full_name', "%{$term}%", caseSensitive: false)
                ->orWhereLike('email', "%{$term}%", caseSensitive: false)
                ->orWhereLike('position', "%{$term}%", caseSensitive: false);
        });
    }

    /**
     * A URL the browser can render for this user's photo, or null.
     *
     * Mirrors BroodcockPhoto::url(): the Supabase bucket is private, so signed
     * temporary URLs are used where the driver supports them, falling back to a
     * plain URL on the local public disk. Reading the disk from the row rather
     * than from config is what makes an old photo still resolve after the
     * default disk changes.
     */
    public function profilePhotoUrl(int $minutes = 30): ?string
    {
        if (blank($this->profile_photo_path)) {
            return null;
        }

        $disk = Storage::disk($this->profile_photo_disk ?: config('filesystems.default'));

        if (! $disk->exists($this->profile_photo_path)) {
            return null;
        }

        return $disk->providesTemporaryUrls()
            ? $disk->temporaryUrl($this->profile_photo_path, now()->addMinutes($minutes))
            : $disk->url($this->profile_photo_path);
    }

    public function hasProfilePhoto(): bool
    {
        return filled($this->profile_photo_path);
    }
}
