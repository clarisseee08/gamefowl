<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repairs matings whose "chicks registered" count was typed by hand.
 *
 * Until 12 November 2026 the mating form had an "Offspring Registered" box.
 * A number typed there was saved as breeding_records.offspring_count without
 * creating a single bird, so the mating then claimed chicks that do not exist:
 * its page read "6 of 6 chicks registered" above "No chicks registered yet",
 * and "Register chicks" was hidden because hatched minus registered was zero.
 * The box is gone; this fixes the rows it left behind.
 *
 * Each mating's count is lowered to the number of birds that have its sire
 * AND its dam, and only where it claims more than that. It is never raised.
 *
 * Why that bound is safe: every chick GenerateOffspring registers is created
 * with the mating's sire_id and dam_id, so a mating's real chicks are always
 * among the birds of its pair. The pair's birds can include chicks of another
 * mating of the same pair, so the bound can be higher than the truth, never
 * lower - no chick can become registrable twice.
 *
 * Archived birds are counted (no deleted_at filter): an archived chick was
 * still registered, and can be restored.
 */
return new class extends Migration
{
    public function up(): void
    {
        $birdsOfThePair = '(select count(*) from broodcocks'
            .' where broodcocks.sire_id = breeding_records.sire_id'
            .' and broodcocks.dam_id = breeding_records.dam_id)';

        DB::update(
            "update breeding_records set offspring_count = {$birdsOfThePair}"
            ." where offspring_count > {$birdsOfThePair}"
        );
    }

    /**
     * Nothing to undo: the numbers it replaced were wrong, and were not kept.
     */
    public function down(): void
    {
        //
    }
};
