<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Enums\BroodcockStatus;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Health\Index as HealthIndex;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Deleting a bird keeps its history - the delete dialog promises exactly that -
 * so every screen that lists the history must still open and still say which
 * bird it was. These screens used to answer HTTP 500 the moment a bird with
 * records was deleted, and stayed broken until it was restored by hand.
 *
 * Follow-ups are the exception: a bird that is gone is not due a vaccination,
 * so the schedule and the dashboard leave it out.
 */
final class DeletedBirdHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_health_list_still_names_a_deleted_bird_without_linking_it(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Tandang', 'band_number' => 'KL-2001']);
        HealthRecord::factory()->for($bird)->create();
        $bird->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('health.index'))
            ->assertOk()
            ->assertSee('KL-2001')
            ->assertSee('Tandang &middot; removed', escape: false)
            ->assertDontSee('href="'.route('broodcocks.show', $bird).'"', escape: false);
    }

    public function test_a_record_of_a_deleted_bird_can_still_be_deleted(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Tandang']);
        $record = HealthRecord::factory()->for($bird)->create();
        $bird->delete();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(HealthIndex::class)
            ->call('confirmDelete', $record->id)
            ->assertSee('Tandang')
            ->call('delete')
            ->assertDispatched('toast');

        $this->assertSoftDeleted($record);
    }

    public function test_the_vaccination_schedule_lists_only_birds_still_on_the_farm(): void
    {
        $birds = [
            'on farm' => Broodcock::factory()->create(['name' => 'Kingpin']),
            'deceased' => Broodcock::factory()->deceased()->create(['name' => 'Tigre']),
            'sold' => Broodcock::factory()->create(['name' => 'Bulalakaw', 'status' => BroodcockStatus::Sold]),
            'deleted' => Broodcock::factory()->create(['name' => 'Tandang']),
            'outside' => Broodcock::factory()->external()->create(['name' => 'Borrowed Cock']),
        ];

        foreach ($birds as $bird) {
            HealthRecord::factory()->for($bird)->create([
                'checkup_date' => today()->subDays(90),
                'next_due_date' => today()->subDays(10),
            ]);
        }
        $birds['deleted']->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('health.schedule'))
            ->assertOk()
            ->assertSee('Kingpin')
            ->assertDontSee('Tigre')
            ->assertDontSee('Bulalakaw')
            ->assertDontSee('Tandang')
            ->assertDontSee('Borrowed Cock');
    }

    public function test_the_dashboard_does_not_ask_for_birds_that_have_left_the_farm(): void
    {
        $onFarm = Broodcock::factory()->create(['name' => 'Kingpin']);
        $deceased = Broodcock::factory()->deceased()->create(['name' => 'Tigre']);

        foreach ([$onFarm, $deceased] as $bird) {
            HealthRecord::factory()->for($bird)->create([
                'checkup_date' => today()->subDays(90),
                'next_due_date' => today()->subDays(10),
            ]);
        }

        $dashboard = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Overview::class)
            ->assertSee('Kingpin')
            ->assertDontSee('Tigre');

        $this->assertSame(1, $dashboard->instance()->overdueCount);
    }

    public function test_the_breeding_list_still_names_a_deleted_parent_without_linking_it(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Tandang']);
        $dam = Broodcock::factory()->female()->create(['name' => 'Marikit']);
        BreedingRecord::factory()->forPair($sire, $dam)->create();
        $sire->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('breeding.index'))
            ->assertOk()
            ->assertSee('Tandang')
            ->assertSee('Marikit')
            ->assertDontSee('href="'.route('broodcocks.show', $sire).'"', escape: false)
            ->assertSee('href="'.route('broodcocks.show', $dam).'"', escape: false);
    }

    public function test_a_breeding_record_with_a_deleted_parent_still_opens(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Tandang']);
        $dam = Broodcock::factory()->female()->create(['name' => 'Marikit']);
        $record = BreedingRecord::factory()->forPair($sire, $dam)->create();
        $sire->delete();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('breeding.show', $record))
            ->assertOk()
            ->assertSee('Tandang × Marikit')
            ->assertSee('Removed from the records')
            ->assertDontSee('href="'.route('broodcocks.show', $sire).'"', escape: false);
    }
}
