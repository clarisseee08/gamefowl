<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration that removed customer accounts.
 *
 * It runs once on the live database, against rows nobody can inspect
 * beforehand, so what it does to existing data is pinned here rather than
 * trusted: every customer goes - soft-deleted ones too - and nobody else does.
 */
final class CustomerRoleRemovalMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_09_232637_remove_customer_role_from_users.php');
    }

    /** Puts the column back as it was before the migration, with a customer in it. */
    private function insertUser(string $email, string $role, ?string $deletedAt = null): int
    {
        return DB::table('users')->insertGetId([
            'full_name' => $email,
            'email' => $email,
            'password' => 'x',
            'role' => $role,
            'is_active' => true,
            'deleted_at' => $deletedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_deletes_every_customer_and_keeps_everyone_else(): void
    {
        $migration = $this->migration();
        $migration->down();

        $owner = $this->insertUser('owner@example.test', 'owner');
        $staff = $this->insertUser('staff@example.test', 'staff');
        $customer = $this->insertUser('customer@example.test', 'customer');
        $trashed = $this->insertUser('trashed@example.test', 'customer', now()->toDateTimeString());

        DB::table('sessions')->insert([
            ['id' => 'customer-session', 'user_id' => $customer, 'payload' => '', 'last_activity' => time()],
            ['id' => 'owner-session', 'user_id' => $owner, 'payload' => '', 'last_activity' => time()],
        ]);

        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $owner, 'role' => 'owner']);
        $this->assertDatabaseHas('users', ['id' => $staff, 'role' => 'staff']);
        $this->assertDatabaseMissing('users', ['id' => $customer]);
        $this->assertDatabaseMissing('users', ['id' => $trashed]);

        // Nobody stays signed in as an account that no longer exists.
        $this->assertDatabaseMissing('sessions', ['id' => 'customer-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'owner-session']);
    }

    public function test_the_column_no_longer_accepts_customer(): void
    {
        $this->expectException(QueryException::class);

        $this->insertUser('late@example.test', 'customer');
    }

    /** A new account must be given a role on purpose; none is assumed. */
    public function test_the_column_has_no_default_role(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'full_name' => 'No Role',
            'email' => 'norole@example.test',
            'password' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
