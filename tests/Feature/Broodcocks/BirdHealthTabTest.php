<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Show;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Health tab on a bird's page.
 *
 * Health and performance each have their own tab. The performance timeline
 * used to be repeated on this one; the owner had it taken off, because the
 * Performance tab beside it already shows exactly that.
 *
 * This test exists because nothing else renders the tab: every other Show test
 * uses the default 'overview', so a broken tab body would not fail the suite.
 */
final class BirdHealthTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_health_tab_renders_the_health_history_alone(): void
    {
        $bird = Broodcock::factory()->male()->create();
        HealthRecord::factory()->for($bird)->create();
        PerformanceRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['broodcock' => $bird])
            ->set('tab', 'health')
            ->assertOk()
            ->assertSee('Add Health Record')
            ->assertDontSee('Add Performance Record');
    }

    public function test_staff_can_reach_edit_from_the_health_tab(): void
    {
        $bird = Broodcock::factory()->create();
        $record = HealthRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['broodcock' => $bird])
            ->set('tab', 'health')
            ->assertSee(route('health.edit', $record), escape: false);
    }

    public function test_the_performance_tab_still_renders_on_its_own(): void
    {
        $bird = Broodcock::factory()->male()->create();
        PerformanceRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['broodcock' => $bird])
            ->set('tab', 'performance')
            ->assertOk()
            ->assertSee('Add Performance Record');
    }
}
