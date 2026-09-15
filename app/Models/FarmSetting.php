<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\FarmProfile;
use Illuminate\Database\Eloquent\Model;

/**
 * The farm's own name and contact details. One row, always.
 *
 * Read almost nowhere directly - the footer, the sidebar, the error pages,
 * head-meta, the dashboard greeting and the dompdf report layout all go on
 * reading config('gfms.farm.*') exactly as they did when these were
 * environment variables. App\Support\FarmProfile is what makes that true: it
 * pushes this row into that config array once per request. Nine call sites
 * therefore did not have to change, including the PDF layout, which is a CSS
 * 2.1 engine that can only be handed plain scalars.
 *
 * @property string $farm_name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $hours
 * @property string|null $visitor_note
 */
final class FarmSetting extends Model
{
    protected $fillable = [
        'farm_name',
        'address',
        'phone',
        'email',
        'hours',
        'visitor_note',
    ];

    /**
     * The single row, or an unsaved one carrying the config defaults.
     *
     * Never creates a second row and never writes on a read. A caller that
     * gets the unsaved instance is on a database whose migration has not run;
     * it gets the environment fallback rather than an exception, which is what
     * keeps `artisan migrate` itself able to boot.
     */
    public static function current(): self
    {
        return self::query()->oldest('id')->first() ?? new self([
            'farm_name' => (string) config('gfms.farm.name'),
            'address' => (string) config('gfms.farm.address'),
            'phone' => (string) config('gfms.farm.phone'),
            'email' => (string) config('gfms.farm.email'),
            'hours' => (string) config('gfms.farm.hours'),
        ]);
    }

    /**
     * Re-read the cached copy whenever the row changes.
     *
     * On the model rather than in the Livewire component, for the same reason
     * Broodcock invalidates RecordCache here: a future seeder, console command
     * or tinker session that edits this row must invalidate too, and only a
     * model event catches all of them.
     */
    protected static function booted(): void
    {
        $refresh = static function (): void {
            FarmProfile::refresh();
        };

        self::saved($refresh);
        self::deleted($refresh);
    }
}
