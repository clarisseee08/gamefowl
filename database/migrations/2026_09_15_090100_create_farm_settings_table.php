<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The farm's own name and contact details, editable by the owner.
 *
 * These used to be GFMS_FARM_* environment variables, which meant the one
 * person who most needs to change them - the farm owner, who is not a
 * developer and has no Render dashboard - was the one person who could not.
 * Changing a phone number meant editing a file and redeploying.
 *
 * EXACTLY ONE ROW, and nothing in the schema enforces that, because nothing
 * sensibly can - a unique index needs a column to be unique on, and a CHECK
 * constraint on a subquery is not portable between SQLite and Postgres. It is
 * enforced in App\Models\FarmSetting::current(), which reads the first row and
 * never creates a second, and in Settings\Farm, which only ever updates.
 *
 * THE ROW IS INSERTED HERE RATHER THAN IN A SEEDER, deliberately. Render runs
 * migrations and does not run seeders, so a seeded-only row would leave the
 * live site with a nameless farm and a blank Visit section - the failure would
 * appear only in production, which is the worst place to discover it. The
 * table is meaningless without its single row, so creating it is part of
 * creating the table.
 *
 * The values are the farm's real ones. They are not a secret: every one of
 * them is printed on a public page, which is the entire reason they exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');

            /*
             * Not nullable, and the only one. The farm's name is the page
             * title, the sidebar, the browser tab and every PDF footer - a
             * blank here is a blank in all of them, whereas a missing phone
             * number simply omits a row.
             */
            $table->string('farm_name', 120);

            $table->string('address', 255)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('hours', 120)->nullable();

            /*
             * A paragraph the owner can write for the public Visit section -
             * "ring ahead on Sundays", "parking is by the blue gate". text
             * rather than string because it is prose and the owner should not
             * meet a character limit mid-sentence; the form caps it at 500 so
             * it cannot swallow the page.
             */
            $table->text('visitor_note')->nullable();

            $table->timestamps();
        });

        DB::table('farm_settings')->insert([
            'farm_name' => 'SSGuad Game Farm',
            'address' => 'Guimbal, Iloilo',
            'phone' => '+639123456789',
            'email' => 'gfms_inquiries@gmail.com',
            'hours' => 'Monday to Saturday, 8AM to 5PM',
            'visitor_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_settings');
    }
};
