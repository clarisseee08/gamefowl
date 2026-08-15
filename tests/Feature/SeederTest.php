<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Enums\UserRole;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\Pen;
use App\Models\PerformanceRecord;
use App\Models\User;
use Database\Seeders\BroodcockSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The seeded data is what the panel will see during the defense, so its shape
 * is a requirement rather than a convenience. These tests run against SQLite;
 * the CHECK constraints they mirror are enforced for real on PostgreSQL.
 */
final class SeederTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<class-string, int> */
    private const EXPECTED_COUNTS = [
        User::class => 3,
        Pen::class => 4,
        Broodcock::class => 30,
        HealthRecord::class => 60,
        BreedingRecord::class => 16,
        PerformanceRecord::class => 40,
        MortalityRecord::class => 3,
    ];

    public function test_seeding_produces_the_expected_row_counts(): void
    {
        $this->seed();

        foreach (self::EXPECTED_COUNTS as $model => $expected) {
            $this->assertSame(
                $expected,
                $model::query()->count(),
                "Unexpected row count for {$model}.",
            );
        }
    }

    public function test_the_three_demo_accounts_exist_with_the_right_roles(): void
    {
        $this->seed();

        $expected = [
            UserSeeder::OWNER_EMAIL => UserRole::Owner,
            UserSeeder::STAFF_EMAIL => UserRole::Staff,
            UserSeeder::CUSTOMER_EMAIL => UserRole::Customer,
        ];

        foreach ($expected as $email => $role) {
            $user = User::query()->where('email', $email)->first();

            $this->assertInstanceOf(User::class, $user, "Missing demo account {$email}.");
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->is_active);
            $this->assertNotNull($user->email_verified_at);
        }
    }

    public function test_the_two_internal_accounts_have_a_position_recorded(): void
    {
        $this->seed();

        $this->assertNotNull(User::query()->where('email', UserSeeder::OWNER_EMAIL)->value('position'));
        $this->assertNotNull(User::query()->where('email', UserSeeder::STAFF_EMAIL)->value('position'));
        $this->assertNull(User::query()->where('email', UserSeeder::CUSTOMER_EMAIL)->value('position'));
    }

    public function test_every_demo_account_can_authenticate_with_the_documented_password(): void
    {
        $this->seed();

        $emails = [
            UserSeeder::OWNER_EMAIL,
            UserSeeder::STAFF_EMAIL,
            UserSeeder::CUSTOMER_EMAIL,
        ];

        foreach ($emails as $email) {
            $this->assertTrue(
                Auth::attempt(['email' => $email, 'password' => UserSeeder::DEMO_PASSWORD]),
                "{$email} could not sign in with the documented password.",
            );

            Auth::logout();
        }
    }

    public function test_at_least_one_bird_has_a_complete_three_generation_ancestry(): void
    {
        $this->seed();

        $bird = Broodcock::query()
            ->with(Broodcock::PEDIGREE_RELATIONS)
            ->where('band_number', BroodcockSeeder::COMPLETE_PEDIGREE_BAND)
            ->first();

        $this->assertInstanceOf(Broodcock::class, $bird);

        // 2 parents + 4 grandparents + 8 great-grandparents.
        $this->assertCount(14, Broodcock::PEDIGREE_RELATIONS);

        foreach (Broodcock::PEDIGREE_RELATIONS as $path) {
            $ancestor = $bird;

            foreach (explode('.', $path) as $step) {
                $ancestor = $ancestor?->{$step};
            }

            $this->assertInstanceOf(
                Broodcock::class,
                $ancestor,
                "Ancestor slot '{$path}' is empty on the demo bird.",
            );
        }
    }

    public function test_a_sire_is_always_male_and_a_dam_always_female(): void
    {
        $this->seed();

        $this->assertSame(0, Broodcock::query()->whereHas('sire', fn ($q) => $q->where('sex', 'female'))->count());
        $this->assertSame(0, Broodcock::query()->whereHas('dam', fn ($q) => $q->where('sex', 'male'))->count());
        $this->assertSame(0, BreedingRecord::query()->whereHas('sire', fn ($q) => $q->where('sex', 'female'))->count());
        $this->assertSame(0, BreedingRecord::query()->whereHas('dam', fn ($q) => $q->where('sex', 'male'))->count());
    }

    public function test_some_birds_are_deliberately_left_without_a_full_pedigree(): void
    {
        $this->seed();

        // Founders have no parents at all, and a couple of later birds have
        // exactly one parent recorded - both are states the pedigree screen
        // has to render as "Not recorded".
        $this->assertGreaterThan(0, Broodcock::query()->whereNull('sire_id')->whereNull('dam_id')->count());
        $this->assertGreaterThan(0, Broodcock::query()->whereNull('sire_id')->whereNotNull('dam_id')->count());
        $this->assertGreaterThan(0, Broodcock::query()->whereNotNull('sire_id')->whereNull('dam_id')->count());
    }

    public function test_young_birds_are_left_unbanded(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(2, Broodcock::query()->whereNull('band_number')->count());
    }

    public function test_the_flock_spans_three_bloodlines(): void
    {
        $this->seed();

        $bloodlines = Broodcock::query()->distinct()->pluck('bloodline')->all();

        $this->assertEqualsCanonicalizing(['Hatch', 'Kelso', 'Sweater'], $bloodlines);
    }

    public function test_overdue_and_due_soon_health_records_both_exist(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(6, HealthRecord::query()->overdue()->count());
        $this->assertGreaterThanOrEqual(8, HealthRecord::query()->dueSoon()->count());
    }

    public function test_health_records_never_schedule_a_follow_up_before_the_checkup(): void
    {
        $this->seed();

        // Mirrors the health_next_due_after_checkup CHECK constraint, which
        // only exists on PostgreSQL.
        $this->assertSame(
            0,
            HealthRecord::query()
                ->whereNotNull('next_due_date')
                ->whereColumn('next_due_date', '<', 'checkup_date')
                ->count(),
        );
    }

    public function test_breeding_records_respect_the_egg_funnel(): void
    {
        $this->seed();

        $this->assertSame(0, BreedingRecord::query()->whereColumn('eggs_fertile', '>', 'eggs_set')->count());
        $this->assertSame(0, BreedingRecord::query()->whereColumn('eggs_hatched', '>', 'eggs_fertile')->count());
        $this->assertSame(0, BreedingRecord::query()->whereColumn('offspring_count', '>', 'eggs_hatched')->count());
        $this->assertSame(0, BreedingRecord::query()->whereColumn('sire_id', '=', 'dam_id')->count());
    }

    public function test_breeding_records_include_a_strong_pair_a_weak_pair_and_unregistered_offspring(): void
    {
        $this->seed();

        $rates = BreedingRecord::query()->get()
            ->map(fn (BreedingRecord $record): ?float => $record->fertilityRate())
            ->filter()
            ->all();

        $this->assertGreaterThanOrEqual(90.0, max($rates), 'No strong pair to demonstrate.');
        $this->assertLessThanOrEqual(50.0, min($rates), 'No weak pair to demonstrate.');

        $this->assertGreaterThan(
            0,
            BreedingRecord::query()->where('eggs_hatched', '>', 0)->where('offspring_count', 0)->count(),
            'Nothing left for the "Register chicks" button to act on.',
        );
    }

    public function test_performance_records_cover_every_event_type(): void
    {
        $this->seed();

        foreach (PerformanceEventType::cases() as $type) {
            $this->assertGreaterThan(
                0,
                PerformanceRecord::query()->where('event_type', $type->value)->count(),
                "No {$type->value} records were seeded.",
            );
        }

        foreach ([PerformanceResult::Win, PerformanceResult::Loss, PerformanceResult::Draw] as $result) {
            $this->assertGreaterThan(0, PerformanceRecord::query()->where('result', $result->value)->count());
        }
    }

    public function test_non_contest_events_are_recorded_as_not_applicable(): void
    {
        $this->seed();

        $nonContest = PerformanceRecord::query()
            ->whereIn('event_type', [
                PerformanceEventType::Conditioning->value,
                PerformanceEventType::WeighIn->value,
            ])
            ->get();

        $this->assertGreaterThan(0, $nonContest->count());

        foreach ($nonContest as $record) {
            $this->assertSame(PerformanceResult::NotApplicable, $record->result);
        }
    }

    public function test_every_bird_with_a_mortality_record_is_marked_deceased(): void
    {
        $this->seed();

        $records = MortalityRecord::query()->with('broodcock')->get();

        $this->assertCount(3, $records);

        foreach ($records as $record) {
            $this->assertSame(
                BroodcockStatus::Deceased,
                $record->broodcock->status,
                "{$record->broodcock->name} has a mortality record but is not marked deceased.",
            );
        }

        // And nothing is marked deceased without a death on file.
        $this->assertSame(
            3,
            Broodcock::query()->where('status', BroodcockStatus::Deceased->value)->count(),
        );
    }

    public function test_running_the_seeder_twice_does_not_duplicate_data(): void
    {
        $this->seed();
        $this->seed();

        foreach (self::EXPECTED_COUNTS as $model => $expected) {
            $this->assertSame(
                $expected,
                $model::query()->count(),
                "Re-seeding duplicated rows in {$model}.",
            );
        }

        // The demo accounts must still work after a second run.
        $this->assertTrue(Auth::attempt([
            'email' => UserSeeder::OWNER_EMAIL,
            'password' => UserSeeder::DEMO_PASSWORD,
        ]));
    }
}
