<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every report generation is persisted here so the audit trail promised in
     * the Significance chapter can be demonstrated live during the defense.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('report_type', 80);

            // The exact filters and date range used, so a report can be
            // explained - and reproduced - after the fact. jsonb (not json)
            // so Postgres can index and query inside it.
            $table->jsonb('parameters')->nullable();

            $table->string('format', 10)->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('report_type');
            $table->index('generated_by');
            $table->index('generated_at');
            $table->index(['report_type', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
