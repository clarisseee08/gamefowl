<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Enums\Sex;
use App\Livewire\Breeding\Form;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Recording a mating against a bird the farm does not own.
 *
 * The farm mates its cocks to borrowed and visiting hens, which sire_id/dam_id
 * could not express: both are foreign keys, so a parent that was not already a
 * row could not be entered at all.
 *
 * An outside parent is therefore typed as a name and turned into a real
 * broodcock row, flagged is_external. That is what keeps the pedigree tree
 * whole — a free-text name would leave dam_id NULL and cut the branch above
 * that hen, losing the feature the system is built around.
 */
final class ExternalParentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    // -----------------------------------------------------------------
    // Recording an outside parent
    // -----------------------------------------------------------------

    public function test_a_mating_can_name_a_dam_the_farm_does_not_own(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_id', (string) $sire->id)
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Ilocos Hen')
            ->set('dam_external_bloodline', 'Sweater')
            ->set('mating_date', today()->toDateString())
            ->set('eggs_set', 6)
            ->set('eggs_fertile', 5)
            ->set('eggs_hatched', 4)
            ->set('offspring_count', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', [
            'name' => 'Ilocos Hen',
            'sex' => Sex::Female->value,
            'bloodline' => 'Sweater',
            'is_external' => true,
        ]);

        $dam = Broodcock::query()->where('name', 'Ilocos Hen')->sole();

        // The point of creating a row rather than storing a name: the record
        // still points at a bird, so the pedigree can walk through her.
        $this->assertDatabaseHas('breeding_records', [
            'sire_id' => $sire->id,
            'dam_id' => $dam->id,
        ]);
    }

    public function test_a_mating_can_name_a_sire_the_farm_does_not_own(): void
    {
        $dam = Broodcock::factory()->create(['sex' => Sex::Female]);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_is_external', true)
            ->set('sire_external_name', 'Visiting Cock')
            ->set('dam_id', (string) $dam->id)
            ->set('mating_date', today()->toDateString())
            ->set('eggs_set', 3)
            ->set('eggs_fertile', 2)
            ->set('eggs_hatched', 1)
            ->set('offspring_count', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', [
            'name' => 'Visiting Cock',
            'sex' => Sex::Male->value,
            'is_external' => true,
        ]);
    }

    public function test_both_parents_may_be_outside_birds(): void
    {
        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_is_external', true)
            ->set('sire_external_name', 'Outside Cock')
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Outside Hen')
            ->set('mating_date', today()->toDateString())
            ->set('eggs_set', 2)
            ->set('eggs_fertile', 2)
            ->set('eggs_hatched', 2)
            ->set('offspring_count', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Broodcock::query()->external()->count());
        $this->assertSame(1, BreedingRecord::query()->count());
    }

    // -----------------------------------------------------------------
    // Not duplicating the same borrowed bird
    // -----------------------------------------------------------------

    public function test_naming_the_same_outside_hen_twice_reuses_her_row(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);
        $staff = $this->staff();

        foreach ([today()->subDays(10), today()] as $date) {
            Livewire::actingAs($staff)
                ->test(Form::class)
                ->set('sire_id', (string) $sire->id)
                ->set('dam_is_external', true)
                ->set('dam_external_name', 'Ilocos Hen')
                ->set('mating_date', $date->toDateString())
                ->set('eggs_set', 1)
                ->set('eggs_fertile', 1)
                ->set('eggs_hatched', 1)
                ->set('offspring_count', 0)
                ->call('save')
                ->assertHasNoErrors();
        }

        // One hen, two matings — not two hens. Otherwise she becomes several
        // separate nodes in the pedigree instead of one.
        $this->assertSame(1, Broodcock::query()->where('name', 'Ilocos Hen')->count());
        $this->assertSame(2, BreedingRecord::query()->count());
    }

    public function test_surrounding_whitespace_does_not_create_a_second_row(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);
        $staff = $this->staff();

        foreach (['Ilocos Hen', '  Ilocos Hen  '] as $typed) {
            Livewire::actingAs($staff)
                ->test(Form::class)
                ->set('sire_id', (string) $sire->id)
                ->set('dam_is_external', true)
                ->set('dam_external_name', $typed)
                ->set('mating_date', today()->toDateString())
                ->set('eggs_set', 1)
                ->set('eggs_fertile', 1)
                ->set('eggs_hatched', 1)
                ->set('offspring_count', 0)
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(1, Broodcock::query()->where('name', 'Ilocos Hen')->count());
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    public function test_an_outside_parent_still_needs_a_name(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_id', (string) $sire->id)
            ->set('dam_is_external', true)
            ->set('dam_external_name', '')
            ->set('mating_date', today()->toDateString())
            ->call('save')
            ->assertHasErrors('dam_external_name');

        $this->assertSame(0, BreedingRecord::query()->count());
    }

    public function test_a_dam_is_still_required_when_the_box_is_not_ticked(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_id', (string) $sire->id)
            ->set('mating_date', today()->toDateString())
            ->call('save')
            ->assertHasErrors('dam_id');
    }

    // -----------------------------------------------------------------
    // Outside birds are not farm stock
    // -----------------------------------------------------------------

    public function test_an_outside_bird_is_excluded_from_farm_stock(): void
    {
        $own = Broodcock::factory()->create();
        $outside = Broodcock::factory()->create(['is_external' => true]);

        $farmStock = Broodcock::query()->farmStock()->pluck('id');

        $this->assertTrue($farmStock->contains($own->id));
        $this->assertFalse($farmStock->contains($outside->id));
    }

    public function test_birds_already_on_record_are_farm_stock_by_default(): void
    {
        $bird = Broodcock::factory()->create();

        $this->assertFalse($bird->fresh()->is_external);
    }
}
