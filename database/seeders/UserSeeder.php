<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The three demo accounts, one per role, documented in the README so the panel
 * can sign in as each of them during the defense.
 *
 * Keyed on `email` through updateOrCreate(), so re-running the seeder resets
 * the demo passwords rather than colliding with the UNIQUE index.
 */
final class UserSeeder extends Seeder
{
    public const OWNER_EMAIL = 'owner@ssguad.test';

    public const STAFF_EMAIL = 'staff@ssguad.test';

    public const CUSTOMER_EMAIL = 'customer@ssguad.test';

    /** Documented in the README. Demo data only - never a production password. */
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        // Hash once: bcrypt is deliberately slow, and all three share a password.
        $password = Hash::make(self::DEMO_PASSWORD);

        $accounts = [
            [
                'email' => self::OWNER_EMAIL,
                'full_name' => 'Salvador S. Guadalupe',
                'role' => UserRole::Owner,
                'position' => 'Farm Owner',
                'contact_number' => '09171234567',
                'address' => 'Purok 3, Barangay San Isidro, Cabanatuan City, Nueva Ecija',
            ],
            [
                'email' => self::STAFF_EMAIL,
                'full_name' => 'Marilou D. Ocampo',
                'role' => UserRole::Staff,
                'position' => 'Farm Record Keeper',
                'contact_number' => '09284567890',
                'address' => 'Barangay Bangad, Cabanatuan City, Nueva Ecija',
            ],
            [
                'email' => self::CUSTOMER_EMAIL,
                'full_name' => 'Ricardo B. Villanueva',
                'role' => UserRole::Customer,
                'position' => null,
                'contact_number' => '09399876543',
                'address' => 'Barangay Sto. Domingo, Sta. Rosa, Nueva Ecija',
            ],
        ];

        foreach ($accounts as $account) {
            // withTrashed(): the UNIQUE index on `email` still counts
            // soft-deleted rows, so a trashed demo account must be revived
            // rather than re-inserted.
            $user = User::withTrashed()->firstOrNew(['email' => $account['email']]);

            // forceFill, not fill: `email_verified_at` and `deleted_at` are
            // deliberately outside $fillable, and both must be set here.
            $user->forceFill([
                'full_name' => $account['full_name'],
                'password' => $password,
                'role' => $account['role'],
                'contact_number' => $account['contact_number'],
                'address' => $account['address'],
                'position' => $account['position'],
                'is_active' => true,
                'email_verified_at' => now(),
                'deleted_at' => null,
            ])->save();
        }
    }
}
