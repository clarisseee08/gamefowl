<?php

declare(strict_types=1);

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broodcocks', function (Blueprint $table): void {
            $table->bigIncrements('id');

            // Birds are banded at a certain age, not at hatch, so this is
            // nullable - but must stay unique across the farm when present.
            $table->string('band_number', 64)->nullable()->unique();
            $table->string('name');

            $table->string('breed', 120)->nullable();
            $table->string('bloodline', 120)->nullable();
            $table->enum('class', BroodcockClass::values())->default(BroodcockClass::Ordinary->value);
            $table->enum('sex', Sex::values());

            // Age is DERIVED from date_hatched in an accessor and is deliberately
            // not stored - a stored age is stale the next day.
            $table->date('date_hatched')->nullable();
            $table->date('date_acquired')->nullable();

            // Appearance and identifying features - objective (d).
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('color', 80)->nullable();
            $table->string('comb_type', 80)->nullable();
            $table->string('leg_color', 80)->nullable();
            $table->text('distinguishing_marks')->nullable();

            $table->enum('status', BroodcockStatus::values())->default(BroodcockStatus::Active->value);

            // Pedigree. The thesis class diagram stored bloodline only as a
            // string, which is a label rather than traceability - these two
            // self-referencing keys are what make the pedigree tree real.
            $table->unsignedBigInteger('sire_id')->nullable();
            $table->unsignedBigInteger('dam_id')->nullable();

            $table->unsignedBigInteger('pen_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sire_id')->references('id')->on('broodcocks')->nullOnDelete();
            $table->foreign('dam_id')->references('id')->on('broodcocks')->nullOnDelete();
            $table->foreign('pen_id')->references('id')->on('pens')->nullOnDelete();

            $table->index('sire_id');
            $table->index('dam_id');
            $table->index('pen_id');
            $table->index('status');
            $table->index('class');
            $table->index('sex');
            $table->index('bloodline');
            $table->index('breed');
            // The broodcock index screen filters on status and lists newest first.
            $table->index(['status', 'created_at']);
        });

        DB::statement('alter table broodcocks add constraint broodcocks_weight_non_negative check (weight is null or weight >= 0)');

        // A bird cannot be its own parent. (Deeper cycles are prevented in the
        // application layer, where the full ancestor chain is available.)
        DB::statement('alter table broodcocks add constraint broodcocks_not_own_sire check (sire_id is null or sire_id <> id)');
        DB::statement('alter table broodcocks add constraint broodcocks_not_own_dam check (dam_id is null or dam_id <> id)');

        // A bird cannot be both parents of the same offspring.
        DB::statement('alter table broodcocks add constraint broodcocks_distinct_parents check (sire_id is null or dam_id is null or sire_id <> dam_id)');

        DB::statement('alter table broodcocks add constraint broodcocks_hatched_before_acquired check (date_hatched is null or date_acquired is null or date_hatched <= date_acquired)');
    }

    public function down(): void
    {
        Schema::dropIfExists('broodcocks');
    }
};
