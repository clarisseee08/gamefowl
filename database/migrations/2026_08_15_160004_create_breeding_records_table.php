<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_records', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sire_id');
            $table->unsignedBigInteger('dam_id');
            $table->date('mating_date');

            $table->unsignedInteger('eggs_set')->default(0);
            $table->unsignedInteger('eggs_fertile')->default(0);
            $table->unsignedInteger('eggs_hatched')->default(0);
            $table->unsignedInteger('offspring_count')->default(0);

            // fertility_rate and hatch_rate are COMPUTED ACCESSORS on the model.
            // They are deliberately not columns - a stored rate can contradict
            // the egg counts it is derived from.

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Restrict: a bird with breeding history cannot be silently removed.
            $table->foreign('sire_id')->references('id')->on('broodcocks')->restrictOnDelete();
            $table->foreign('dam_id')->references('id')->on('broodcocks')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('sire_id');
            $table->index('dam_id');
            $table->index('recorded_by');
            $table->index('mating_date');
            $table->index(['sire_id', 'dam_id']);
        });

        // The egg funnel can only narrow: hatched <= fertile <= set.
        DB::statement('alter table breeding_records add constraint breeding_fertile_lte_set check (eggs_fertile <= eggs_set)');
        DB::statement('alter table breeding_records add constraint breeding_hatched_lte_fertile check (eggs_hatched <= eggs_fertile)');

        // Offspring actually registered can never exceed the number hatched.
        DB::statement('alter table breeding_records add constraint breeding_offspring_lte_hatched check (offspring_count <= eggs_hatched)');

        // A bird cannot be mated with itself.
        DB::statement('alter table breeding_records add constraint breeding_distinct_parents check (sire_id <> dam_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_records');
    }
};
