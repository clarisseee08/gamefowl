<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the thesis defense.
 *
 * Two properties matter here and both are deliberate:
 *
 * 1. IDEMPOTENT. `php artisan db:seed` can be run twice without duplicating a
 *    row or tripping a UNIQUE index. Users and pens are keyed on their natural
 *    unique column; the bulk tables are guarded by a sentinel check and return
 *    early once seeded.
 *
 * 2. FEW ROUND TRIPS. The database is a Supabase Postgres in Tokyo, so every
 *    statement costs real latency. Each seeder builds its rows in memory and
 *    writes them with one batched INSERT rather than one INSERT per row.
 *
 * TRANSACTION SCOPE - PER SEEDER, NOT PER RUN.
 *
 * This originally wrapped the whole run in a single transaction so a failure
 * would leave nothing behind. Against the remote database that does not work:
 * the full seed takes well over a minute, and PHP spends most of it building
 * rows in memory rather than talking to the server. The connection therefore
 * sits idle-in-transaction long enough for PostgreSQL to terminate it, and the
 * seed died every time with `SQLSTATE[HY000] ... no connection to the server`
 * partway through - rolling back everything that had already succeeded.
 *
 * Each seeder now gets its own short transaction instead. Every individual
 * seeder still writes atomically, no transaction is held open across the slow
 * in-PHP work between them, and because each seeder carries its own sentinel
 * guard, re-running after an interruption resumes correctly rather than
 * duplicating rows. Verified against the real Supabase database.
 */
final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** In dependency order: birds before anything that references a bird. */
    private const SEEDERS = [
        UserSeeder::class,
        PenSeeder::class,
        BroodcockSeeder::class,
        HealthRecordSeeder::class,
        BreedingRecordSeeder::class,
        PerformanceRecordSeeder::class,
        // Needs a bird to point one request at, and an owner to have decided
        // the handled ones. Neither is required - it degrades to "the farm
        // generally" and an unattributed decision - but both read better.
        AppointmentSeeder::class,
        // Last: this one flips three birds to `deceased`, and running it after
        // the record seeders keeps their history intact.
        MortalitySeeder::class,
    ];

    /**
     * Seed, tolerating a dropped connection.
     *
     * A full cold seed against the remote database takes around a minute of
     * wall clock, and Supabase's connection pooler will drop a connection that
     * has been held that long - the run died reproducibly partway through with
     * `SQLSTATE[HY000] ... no connection to the server`. The PostgreSQL server
     * itself is not responsible: `idle_in_transaction_session_timeout` and
     * `idle_session_timeout` are both 0, and `statement_timeout` is two minutes
     * against statements that take milliseconds.
     *
     * So each seeder is run on a freshly-established connection, and a dropped
     * connection is retried once rather than failing the whole run. Because
     * every seeder carries its own sentinel guard, a retry re-runs only the
     * seeder that was interrupted and never duplicates rows.
     */
    public function run(): void
    {
        foreach (self::SEEDERS as $seeder) {
            $this->runSeeder($seeder);
        }
    }

    private function runSeeder(string $seeder, int $attempt = 1): void
    {
        // Start each seeder on a fresh connection rather than inheriting one
        // that has already been open for the whole run.
        if ($this->canReconnect()) {
            DB::reconnect();
        }

        try {
            DB::transaction(fn () => $this->call($seeder));
        } catch (\Throwable $e) {
            if ($attempt >= 3 || ! $this->isLostConnection($e)) {
                throw $e;
            }

            $this->command?->warn("  Connection dropped during {$seeder} - reconnecting and retrying (attempt {$attempt}).");

            $this->runSeeder($seeder, $attempt + 1);
        }
    }

    /**
     * Whether reconnecting is safe on the current connection.
     *
     * It is NOT safe on in-memory SQLite: that database exists only for the
     * lifetime of its connection, so reconnecting silently discards every table
     * and every row. The test suite runs on `:memory:`, which is how this was
     * caught. Reconnecting only helps against a real remote server anyway.
     */
    private function canReconnect(): bool
    {
        $connection = DB::getDefaultConnection();

        if (config("database.connections.{$connection}.driver") === 'sqlite') {
            return false;
        }

        return true;
    }

    /** Distinguishes a dropped connection from a genuine data error. */
    private function isLostConnection(\Throwable $e): bool
    {
        $message = $e->getMessage();

        foreach ([
            'no connection to the server',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Lost connection',
            'gone away',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
