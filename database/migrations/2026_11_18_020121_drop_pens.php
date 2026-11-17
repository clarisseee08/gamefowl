<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pens are gone, completely.
 *
 * Their screens were removed in September, but the data stayed: the table,
 * broodcocks.pen_id, a Pen dropdown when registering chicks and a column on
 * the inventory report. A pen's location and capacity were shown nowhere and
 * could be changed nowhere, while the class diagram and the ERD still drew
 * them - a half-removal, the thing routes/web.php already called the worst of
 * both. The owner chose the whole removal on 18 Nov 2026.
 *
 * Nothing on the live site pointed at a pen (0 birds with a pen_id, checked
 * the same day), so this drops a handful of reference rows and an empty
 * column. down() puts the structure back; the rows were never data the farm
 * entered, they came from PenSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->dropForeign(['pen_id']);
            $table->dropIndex(['pen_id']);
            $table->dropColumn('pen_id');
        });

        Schema::dropIfExists('pens');
    }

    public function down(): void
    {
        Schema::create('pens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('broodcocks', function (Blueprint $table): void {
            $table->unsignedBigInteger('pen_id')->nullable();
            $table->foreign('pen_id')->references('id')->on('pens')->nullOnDelete();
            $table->index('pen_id');
        });
    }
};
