<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos are archived, not erased.
 *
 * Every other farm record was already soft-deleted; photos were the one thing
 * a removal destroyed, file and all. The farm wants nothing erased, so a
 * removed photo now keeps its row and its file and appears in the Archive,
 * where the owner can restore it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broodcock_photos', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('broodcock_photos', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
