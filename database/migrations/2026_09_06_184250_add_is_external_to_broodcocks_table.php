<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a bird that belongs to someone else.
 *
 * The farm mates its cocks to hens it does not own — a borrowed or visiting
 * breeder is normal practice, and before this the breeding form could not
 * record one at all: sire_id and dam_id are foreign keys, so a parent that was
 * not already a row simply could not be entered.
 *
 * The alternative was a pair of free-text name columns. That was rejected
 * because it puts the outside parent outside the pedigree: sire_id/dam_id stay
 * NULL, and the three-generation family tree — the feature that distinguishes
 * this system from the prior arts — loses the whole branch above that hen.
 * Creating a real row keeps the foreign keys intact and keeps the tree whole.
 *
 * What this flag then buys is the ability to tell the two apart. An outside hen
 * is a pedigree node, not livestock: she is not in the farm's care, has no pen,
 * no health schedule and no mortality to record. Reports and inventory counts
 * that mean "birds this farm owns" filter on this column.
 *
 * Defaults to FALSE, so every bird already on record stays farm stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->boolean('is_external')->default(false)->after('status');

            // "Farm stock only" is the common read — every inventory screen and
            // count wants it — so it is worth an index rather than a scan.
            $table->index(['is_external', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->dropIndex(['is_external', 'status']);
            $table->dropColumn('is_external');
        });
    }
};
