<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboard\Overview;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    public function test_an_owner_sees_the_dashboard(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('dashboard'))->assertOk();
    }

    public function test_a_record_keeper_sees_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('dashboard'))->assertOk();
    }

    /**
     * The dashboard is entirely internal figures, so a customer is sent to the
     * catalogue instead. The Policy is what protects the data; this is the
     * courtesy redirect on top of it.
     */
    public function test_a_customer_is_redirected_to_the_catalogue(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('catalog.index'));
    }

    public function test_a_customer_cannot_reach_the_dashboard_component_directly(): void
    {
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Overview::class)
            ->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Figures
    // -----------------------------------------------------------------

    public function test_the_flock_counts_exclude_sold_and_deceased_from_on_farm(): void
    {
        Broodcock::factory()->count(3)->create(['status' => 'active']);
        Broodcock::factory()->count(2)->create(['status' => 'breeding']);
        Broodcock::factory()->create(['status' => 'sold']);
        Broodcock::factory()->create(['status' => 'deceased']);

        $flock = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->instance()
            ->flock;

        $this->assertSame(7, $flock['total']);
        $this->assertSame(5, $flock['on_farm'], 'Sold and deceased birds must not count as on-farm.');
        $this->assertSame(3, $flock['active']);
        $this->assertSame(2, $flock['breeding']);
    }

    /**
     * The trend must weight by egg volume. Averaging each mating's percentage
     * would let a 2-egg mating count as much as a 100-egg one.
     */
    public function test_the_breeding_trend_is_weighted_by_egg_volume_not_averaged(): void
    {
        $matingDate = now()->startOfMonth()->addDays(5);

        BreedingRecord::factory()->create([
            'mating_date' => $matingDate,
            'eggs_set' => 2, 'eggs_fertile' => 2, 'eggs_hatched' => 2, 'offspring_count' => 0,
        ]);
        BreedingRecord::factory()->create([
            'mating_date' => $matingDate,
            'eggs_set' => 100, 'eggs_fertile' => 50, 'eggs_hatched' => 25, 'offspring_count' => 0,
        ]);

        $overall = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->instance()
            ->breedingOverall;

        // Correct: 52 fertile / 102 set = 51.0%.
        // Naive average of 100% and 50% would be 75% - which is wrong.
        $this->assertSame(51.0, $overall['fertility']);
        $this->assertNotSame(75.0, $overall['fertility']);
    }

    public function test_rates_are_null_rather_than_zero_when_there_is_no_breeding_data(): void
    {
        $overall = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->instance()
            ->breedingOverall;

        // "No matings recorded" is a different claim from "0% fertility".
        $this->assertNull($overall['fertility']);
        $this->assertNull($overall['hatch']);
        $this->assertSame(0, $overall['matings']);
    }

    public function test_overdue_vaccinations_are_surfaced(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bantay']);
        HealthRecord::factory()->for($bird)->create([
            'checkup_date' => today()->subDays(90),
            'next_due_date' => today()->subDays(20),
        ]);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->assertSee('Bantay')
            ->assertSee('days overdue');
    }

    public function test_an_up_to_date_flock_says_so_rather_than_showing_an_empty_list(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->assertSee('The flock is up to date.');
    }

    // -----------------------------------------------------------------
    // Performance - the dashboard reads from every module
    // -----------------------------------------------------------------

    public function test_the_dashboard_query_count_does_not_grow_with_the_size_of_the_farm(): void
    {
        $staff = User::factory()->staff()->create();

        // Both phases must contain the SAME KINDS of data, not just different
        // volumes. Laravel skips an eager-load query entirely when the parent
        // collection is empty, so a phase with no overdue records would issue
        // fewer queries for a reason that has nothing to do with N+1 - and the
        // health factory randomises due dates, which made this flaky.
        $seed = function (int $birds, int $overdue, int $matings): void {
            Broodcock::factory()->count($birds)->create();
            HealthRecord::factory()->count($overdue)->create([
                'checkup_date' => today()->subDays(90),
                'next_due_date' => today()->subDays(20),
            ]);
            BreedingRecord::factory()->count($matings)->create();
        };

        $measure = function () use ($staff): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::actingAs($staff)->test(Overview::class)->html();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $seed(3, 2, 2);
        $small = $measure();

        $seed(25, 12, 15);
        $large = $measure();

        $this->assertSame(
            $small,
            $large,
            "The dashboard took {$small} queries on a small farm and {$large} on a larger one. ".
            'Every figure must be aggregated in SQL - a per-row query here would make the '.
            'landing page unusable against a remote database.'
        );
    }
}
