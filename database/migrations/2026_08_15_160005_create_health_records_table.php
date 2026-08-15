<?php

declare(strict_types=1);

use App\Enums\HealthRecordType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('broodcock_id');
            $table->enum('record_type', HealthRecordType::values());

            $table->string('product_name')->nullable();
            $table->string('dosage', 120)->nullable();
            $table->date('checkup_date');

            // Drives the vaccination compliance report - overdue is
            // next_due_date < today, upcoming is within the warning window.
            $table->date('next_due_date')->nullable();

            $table->string('condition')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('broodcock_id')->references('id')->on('broodcocks')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('broodcock_id');
            $table->index('recorded_by');
            $table->index('record_type');
            $table->index('checkup_date');
            // The compliance report scans for due dates across all birds.
            $table->index('next_due_date');
            $table->index(['broodcock_id', 'checkup_date']);
        });

        DB::statement('alter table health_records add constraint health_next_due_after_checkup check (next_due_date is null or next_due_date >= checkup_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
