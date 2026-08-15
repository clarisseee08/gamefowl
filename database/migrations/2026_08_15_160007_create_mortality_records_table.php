<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Objective (c) names mortality explicitly; the thesis class diagram omits
     * it entirely. Creating a row here also flips the bird's status to
     * `deceased` - both writes happen inside one DB transaction.
     */
    public function up(): void
    {
        Schema::create('mortality_records', function (Blueprint $table): void {
            $table->bigIncrements('id');

            // A bird can only die once, so this is a one-to-one relationship.
            $table->unsignedBigInteger('broodcock_id')->unique();

            $table->date('date_of_death');
            $table->string('cause_of_death');
            $table->string('disposal_method', 120)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('broodcock_id')->references('id')->on('broodcocks')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('recorded_by');
            // The mortality report groups by period and by cause.
            $table->index('date_of_death');
            $table->index('cause_of_death');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortality_records');
    }
};
