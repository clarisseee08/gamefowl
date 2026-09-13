<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Somebody asking to come and see the birds.
 *
 * Written by the public and read by the farm, which makes it the only model
 * here with that direction of travel. $fillable therefore carries only what a
 * visitor supplies: `status`, `handled_by` and `handled_at` are set by the
 * console and are deliberately absent, so a crafted request cannot arrive
 * pre-confirmed.
 */
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * What a visitor may set, and nothing else.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_number',
        'email',
        'preferred_date',
        'preferred_time',
        'party_size',
        'message',
        'broodcock_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'status' => AppointmentStatus::class,
            'handled_at' => 'datetime',
            'party_size' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'handled_by', 'handled_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** The bird that prompted the visit, when one did. */
    public function broodcock(): BelongsTo
    {
        return $this->belongsTo(Broodcock::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * What still needs somebody to act on it.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', AppointmentStatus::Pending->value);
    }
}
