<?php

declare(strict_types=1);

use App\Support\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lock every table against Supabase's data API.
 *
 * The production Table Editor showed "RLS disabled" and every table
 * "UNRESTRICTED", which a thesis reviewer rightly flagged: Supabase serves the
 * public schema through PostgREST to anyone with the project's anon key. The
 * app never uses that API, so row-level security with no policies closes it
 * without touching the app, which reaches the tables as their owner. See
 * RowLevelSecurity; AppServiceProvider re-runs it after every migrate so new
 * tables are locked too. A no-op on SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        RowLevelSecurity::lockOwnedTables(DB::connection());
    }

    public function down(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        $tables = $connection->select(<<<'SQL'
            select c.relname as name
            from pg_class c
            join pg_namespace n on n.oid = c.relnamespace
            where n.nspname = current_schema()
              and c.relkind in ('r', 'p')
              and pg_get_userbyid(c.relowner) = current_user
              and c.relrowsecurity
            SQL);

        foreach ($tables as $table) {
            $connection->statement('alter table "'.str_replace('"', '""', $table->name).'" disable row level security');
        }
    }
};
