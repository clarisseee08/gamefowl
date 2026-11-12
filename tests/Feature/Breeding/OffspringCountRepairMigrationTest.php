<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Actions\Breeding\GenerateOffspring;
use App\Livewire\Breeding\Show;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The migration that repairs matings whose chick count was typed by hand.
 *
 * It runs once on the live database, against rows nobody can inspect
 * beforehand, so what it does to each kind of mating is pinned here: a count
 * with no birds behind it is lowered, a true count is left alone, and nothing
 * is ever raised.
 */
final class OffspringCountRepairMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function repair(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_11_12_140453_repair_offspring_counts_typed_by_hand.php');
        $migration->up();
    }

    /** The mating on the live site: 6 hatched, 6 "registered", no birds. */
    private function stuckMating(int $typed = 6): BreedingRecord
    {
        return BreedingRecord::factory()->withUnregisteredOffspring(6)->create(['offspring_count' => $typed]);
    }

    public function test_a_count_with_no_birds_behind_it_goes_back_to_zero(): void
    {
        $mating = $this->stuckMating();

        $this->repair();

        $this->assertSame(0, $mating->fresh()->offspring_count);
        $this->assertSame(6, $mating->fresh()->unregisteredOffspring());
    }

    public function test_the_repaired_mating_offers_its_chicks_for_registration_again(): void
    {
        $mating = $this->stuckMating();
        $this->repair();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Show::class, ['record' => $mating->fresh()])
            ->assertSee('Register 6 chicks')
            ->assertDontSee('6 of 6');
    }

    public function test_a_count_is_lowered_to_the_birds_that_exist(): void
    {
        $mating = $this->stuckMating();
        Broodcock::factory()->count(2)->create(['sire_id' => $mating->sire_id, 'dam_id' => $mating->dam_id]);

        $this->repair();

        $this->assertSame(2, $mating->fresh()->offspring_count);
    }

    public function test_chicks_registered_the_proper_way_are_left_alone(): void
    {
        $mating = BreedingRecord::factory()->withUnregisteredOffspring(6)->create();
        app(GenerateOffspring::class)->handle($mating, 4);

        $this->repair();

        $this->assertSame(4, $mating->fresh()->offspring_count);
    }

    /** An archived chick was still registered, and can be restored. */
    public function test_archived_chicks_still_count(): void
    {
        $mating = BreedingRecord::factory()->withUnregisteredOffspring(6)->create();
        app(GenerateOffspring::class)->handle($mating, 2);
        Broodcock::query()->where('sire_id', $mating->sire_id)->first()->delete();

        $this->repair();

        $this->assertSame(2, $mating->fresh()->offspring_count);
    }

    /** Birds of the pair that were never registered from this mating do not raise its count. */
    public function test_a_count_is_never_raised(): void
    {
        $mating = BreedingRecord::factory()->withUnregisteredOffspring(6)->create();
        Broodcock::factory()->count(3)->create(['sire_id' => $mating->sire_id, 'dam_id' => $mating->dam_id]);

        $this->repair();

        $this->assertSame(0, $mating->fresh()->offspring_count);
    }

    public function test_only_the_mating_that_is_wrong_changes(): void
    {
        $stuck = $this->stuckMating();
        $fine = BreedingRecord::factory()->withUnregisteredOffspring(6)->create();
        app(GenerateOffspring::class)->handle($fine, 6);

        $this->repair();

        $this->assertSame(0, $stuck->fresh()->offspring_count);
        $this->assertSame(6, $fine->fresh()->offspring_count);
    }
}
