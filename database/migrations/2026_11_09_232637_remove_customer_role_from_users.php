<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customer accounts are gone: the catalogue and every bird's page are public,
 * so an account gave a customer nothing a guest does not already see.
 *
 * Every customer row is deleted outright - soft-deleted ones too, because the
 * column cannot keep a value it no longer allows. Nothing is lost with them:
 * customers could not record anything, and every foreign key to users is
 * nullOnDelete. Their sessions go too, so nobody stays signed in as one.
 *
 * The column keeps no default. A new account must be given a role on purpose;
 * defaulting to either remaining role would hand out access by accident.
 *
 * The values are written out here rather than read from UserRole, so this
 * migration still means the same thing if the enum changes again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $customerIds = DB::table('users')->where('role', 'customer')->pluck('id');

        DB::table('sessions')->whereIn('user_id', $customerIds)->delete();
        DB::table('users')->whereIn('id', $customerIds)->delete();

        $this->allowRoles(['owner', 'staff'], default: null);
    }

    /**
     * Puts the value back. The deleted accounts themselves cannot be restored.
     */
    public function down(): void
    {
        $this->allowRoles(['owner', 'staff', 'customer'], default: 'customer');
    }

    /**
     * Restricts users.role to the given values.
     *
     * PostgreSQL stores a Laravel enum as varchar plus a named CHECK
     * constraint, and Blueprint::change() cannot alter that - it emits
     * "alter column ... type varchar(255) check (...)", which PostgreSQL
     * rejects - so the constraint is replaced by name. SQLite (the test
     * database) has no ALTER for constraints at all; change() rebuilds the
     * table there.
     *
     * @param  list<string>  $roles
     */
    private function allowRoles(array $roles, ?string $default): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $allowed = implode(', ', array_map(fn (string $role): string => "'{$role}'", $roles));

            DB::statement('alter table users drop constraint if exists users_role_check');
            DB::statement("alter table users add constraint users_role_check check (role in ({$allowed}))");
            DB::statement($default === null
                ? 'alter table users alter column role drop default'
                : "alter table users alter column role set default '{$default}'");

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($roles, $default): void {
            $column = $table->enum('role', $roles);

            if ($default !== null) {
                $column->default($default);
            }

            $column->change();
        });
    }
};
