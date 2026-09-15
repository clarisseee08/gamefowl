<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FarmSetting;
use Illuminate\Database\Seeder;

/**
 * The farm's name and contact details.
 *
 * The create migration already inserts this row, because Render runs
 * migrations and does not run seeders - a live site cannot be left waiting for
 * a seeder that never runs. So on a normally migrated database this seeder
 * finds the row already there and changes nothing.
 *
 * It exists for the case the migration cannot cover: a database whose row has
 * been deleted, or one being rebuilt to a known state for a demo. That is why
 * it updates rather than inserts - a second row would be a second farm, and
 * FarmSetting::current() would silently pick whichever was older.
 */
final class FarmProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profile = [
            'farm_name' => 'SSGuad Game Farm',
            'address' => 'Guimbal, Iloilo',
            'phone' => '+639123456789',
            'email' => 'gfms_inquiries@gmail.com',
            'hours' => 'Monday to Saturday, 8AM to 5PM',
        ];

        $existing = FarmSetting::query()->oldest('id')->first();

        if ($existing === null) {
            FarmSetting::query()->create($profile);

            return;
        }

        // The visitor note is deliberately not in $profile and so is not
        // touched here. It is the owner's own prose; a seeder run to restore
        // the contact details should not quietly delete what they wrote.
        $existing->update($profile);
    }
}
