<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Connection;

/**
 * Turns on PostgreSQL row-level security for every table the app owns.
 *
 * Supabase publishes the public schema through its own data API (PostgREST),
 * reachable by anyone holding the project's anon key. With RLS off - the state
 * the Table Editor flagged as "RLS disabled" and every table as
 * "UNRESTRICTED" - that API could read the users table, password hashes and
 * all. Laravel never uses that API: it talks to the database directly as the
 * role that created, and therefore owns, the tables.
 *
 * RLS with no policies denies the API roles every row, while a table's owner
 * is exempt (it is not FORCEd), so the app is unaffected. Only tables owned by
 * the connecting role are touched: ALTER TABLE needs ownership anyway, and
 * locking a table the app does not own could lock the app out of it.
 *
 * It runs from a migration and again after every `artisan migrate`, so a table
 * added later is locked the moment it is created. Elsewhere than PostgreSQL
 * (the SQLite test database) there is nothing to lock.
 */
final class RowLevelSecurity
{
    /**
     * Enable RLS on every owned table that does not have it yet.
     *
     * @return list<string> the tables that were locked by this call
     */
    public static function lockOwnedTables(Connection $connection): array
    {
        if ($connection->getDriverName() !== 'pgsql') {
            return [];
        }

        $tables = array_map(
            static fn (object $row): string => (string) $row->name,
            $connection->select(self::unlockedOwnedTablesQuery()),
        );

        foreach (self::statements($tables) as $statement) {
            $connection->statement($statement);
        }

        return $tables;
    }

    /**
     * Tables in the current schema, owned by the connecting role, with RLS off.
     */
    public static function unlockedOwnedTablesQuery(): string
    {
        return <<<'SQL'
            select c.relname as name
            from pg_class c
            join pg_namespace n on n.oid = c.relnamespace
            where n.nspname = current_schema()
              and c.relkind in ('r', 'p')
              and pg_get_userbyid(c.relowner) = current_user
              and not c.relrowsecurity
            order by c.relname
            SQL;
    }

    /**
     * @param  list<string>  $tables
     * @return list<string>
     */
    public static function statements(array $tables): array
    {
        return array_map(
            static fn (string $table): string => 'alter table '.self::quote($table).' enable row level security',
            $tables,
        );
    }

    private static function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
