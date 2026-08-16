<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a user attach a photo to their own account.
 *
 * Two columns rather than one, matching how broodcock photos already work: the
 * path alone is not enough to fetch a file, because which DISK it lives on is
 * environment-dependent — local in development, Supabase S3 in production. A
 * row that records only the path breaks the moment the disk changes, and the
 * failure looks like a missing photo rather than a configuration problem.
 *
 * Both nullable: an account without a photo is the normal state, not an
 * incomplete one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo_path')->nullable()->after('position');
            $table->string('profile_photo_disk', 40)->nullable()->after('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['profile_photo_path', 'profile_photo_disk']);
        });
    }
};
