<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broodcock_photos', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('broodcock_id');
            $table->string('path');
            $table->string('disk', 40)->default('public');
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            // Photos belong to the bird - deleting the bird removes its photos.
            $table->foreign('broodcock_id')->references('id')->on('broodcocks')->cascadeOnDelete();
            // Keep the photo if the uploading account is removed.
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('broodcock_id');
            $table->index('uploaded_by');
            $table->index(['broodcock_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broodcock_photos');
    }
};
