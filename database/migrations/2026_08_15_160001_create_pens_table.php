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

        // SQLite - which the test suite runs on - cannot ADD CONSTRAINT through
        // ALTER TABLE. These are database-level guarantees for the real
        // PostgreSQL database; Form Request validation enforces the same rules
        // on every write, so behaviour is identical either way.
        if (DB::getDriverName() === 'pgsql') {
            // Capacity is a count of birds; a negative pen has no meaning.
            DB::statement('alter table pens add constraint pens_capacity_non_negative check (capacity >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pens');
    }
};
