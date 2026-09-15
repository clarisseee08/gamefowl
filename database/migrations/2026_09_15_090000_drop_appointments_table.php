<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visit requests are no longer taken through the website.
 *
 * The farm asked for the request form to go: the only way to arrange a visit
 * now is the phone number and email address on the front page. That removed
 * the only thing that could ever write an appointments row, and with no inflow
 * the console queue at /appointments was an inbox that could never receive
 * anything - so the whole feature went rather than half of it. Pens were left
 * half-removed once before in this codebase (routes live, no sidebar) and the
 * note in routes/web.php records how badly that read.
 *
 * THIS DESTROYS DATA. Any visit requests the farm had received go with the
 * table, and `down()` rebuilds the shape but not the rows. On Render this does
 * not run on deploy - docker/entrypoint.sh only migrates when RUN_MIGRATIONS
 * is true, and render.yaml keeps it false because development and production
 * are the same Supabase project. Flip it deliberately, deploy, confirm, flip
 * it back.
 *
 * `down()` is a faithful reverse of 2026_09_13_185526 rather than a stub. The
 * feature can be brought back - its code is one `git revert` away - and a
 * rollback that left no table to revert into would strand it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('appointments');
    }

    public function down(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name', 120);
            $table->string('contact_number', 40);
            $table->string('email', 190)->nullable();
            $table->date('preferred_date');
            $table->enum('preferred_time', ['morning', 'afternoon']);
            $table->unsignedSmallInteger('party_size')->default(1);
            $table->text('message')->nullable();
            $table->foreignId('broodcock_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'declined', 'completed'])
                ->default('pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('preferred_date');
        });
    }
};
