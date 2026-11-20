<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\RowLevelSecurity;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Every table is locked against Supabase's data API (see RowLevelSecurity).
 *
 * The suite runs on SQLite, which has no row-level security, so the PostgreSQL
 * path is driven through a connection double: what must hold is that every
 * owned, unlocked table gets exactly one correctly quoted ALTER, that nothing
 * runs anywhere but PostgreSQL, and that it runs again after every migrate.
 */
final class RowLevelSecurityTest extends TestCase
{
    public function test_each_table_gets_one_quoted_statement(): void
    {
        $this->assertSame([
            'alter table "users" enable row level security',
            'alter table "odd""name" enable row level security',
        ], RowLevelSecurity::statements(['users', 'odd"name']));
    }

    public function test_only_owned_unlocked_tables_in_the_current_schema_are_asked_for(): void
    {
        $query = RowLevelSecurity::unlockedOwnedTablesQuery();

        $this->assertStringContainsString('current_schema()', $query);
        $this->assertStringContainsString('pg_get_userbyid(c.relowner) = current_user', $query);
        $this->assertStringContainsString('not c.relrowsecurity', $query);
    }

    public function test_on_postgres_every_listed_table_is_locked(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('pgsql');
        $connection->shouldReceive('select')->once()->andReturn([
            (object) ['name' => 'broodcocks'],
            (object) ['name' => 'users'],
        ]);
        $connection->shouldReceive('statement')->once()->with('alter table "broodcocks" enable row level security')->andReturnTrue();
        $connection->shouldReceive('statement')->once()->with('alter table "users" enable row level security')->andReturnTrue();

        $this->assertSame(['broodcocks', 'users'], RowLevelSecurity::lockOwnedTables($connection));
    }

    public function test_nothing_runs_on_other_databases(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldNotReceive('select');
        $connection->shouldNotReceive('statement');

        $this->assertSame([], RowLevelSecurity::lockOwnedTables($connection));
        $this->assertSame([], RowLevelSecurity::lockOwnedTables(DB::connection()));
    }

    public function test_it_runs_again_after_every_migrate(): void
    {
        $this->assertTrue(Event::hasListeners(MigrationsEnded::class));

        Event::dispatch(new MigrationsEnded('up'));

        $this->addToAssertionCount(1);
    }
}
