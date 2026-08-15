<?php

declare(strict_types=1);

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The thesis class diagram declared managePerformanceRecords() and
     * viewPerformanceHistory() but modelled no PerformanceRecord class at all.
     * Objective (c) - tracking performance - cannot be met without this table.
     */
    public function up(): void
    {
        Schema::create('performance_records', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('broodcock_id');
            $table->date('event_date');
            $table->enum('event_type', PerformanceEventType::values());

            $table->decimal('weight', 5, 2)->nullable();
            $table->enum('result', PerformanceResult::values())->default(PerformanceResult::NotApplicable->value);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();

            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('broodcock_id')->references('id')->on('broodcocks')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('broodcock_id');
            $table->index('recorded_by');
            $table->index('event_type');
            $table->index('event_date');
            $table->index('result');
            // The per-bird performance timeline reads newest event first.
            $table->index(['broodcock_id', 'event_date']);
        });

        DB::statement('alter table performance_records add constraint performance_rating_range check (rating is null or (rating >= 1 and rating <= 5))');
        DB::statement('alter table performance_records add constraint performance_weight_non_negative check (weight is null or weight >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_records');
    }
};
