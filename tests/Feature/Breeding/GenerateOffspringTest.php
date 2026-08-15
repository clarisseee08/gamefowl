<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Actions\Breeding\GenerateOffspring;
use App\Livewire\Breeding\Show;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Offspring generation is the hinge of the whole pedigree feature: it is what
 * makes parentage a by-product of normal data entry rather than extra typing
 * nobody does. These tests cover both that it works and that it cannot be
 * used to corrupt the counts.
 */
final class GenerateOffspringTest extends TestCase
{
    use RefreshDatabase;

    private function action(): GenerateOffspring
    {
        return app(GenerateOffspring::class);
    }

    public function test_generated_chicks_have_their_sire_and_dam_filled_in(): void
    {
        $sire = Broodcock::factory()->male()->create(['bloodline' => 'Sweater']);
        $dam = Broodcock::factory()->female()->create();
        $record = BreedingRecord::factory()->forPair($sire, $dam)->withUnregisteredOffspring(3)->create();

        $created = $this->action()->handle($record, 3);

        $this->assertCount(3, $created);

        foreach ($created as $chick) {
            $this->assertSame($sire->id, $chick->sire_id, 'Chick was not linked to its sire.');
            $this->assertSame($dam->id, $chick->dam_id, 'Chick was not linked to its dam.');
            $this->assertSame('Sweater', $chick->bloodline, 'Chick did not inherit the sire bloodline.');
            $this->assertNull($chick->band_number, 'Chicks must not be given a fabricated band number.');
        }
    }

    public function test_generating_offspring_increments_the_registered_count(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(5)->create();

        $this->assertSame(5, $record->unregisteredOffspring());

        $this->action()->handle($record, 2);

        $record->refresh();

        $this->assertSame(2, $record->offspring_count);
        $this->assertSame(3, $record->unregisteredOffspring());
    }

    public function test_the_generated_chicks_appear_in_their_parents_pedigree(): void
    {
        $sire = Broodcock::factory()->male()->create();
        $dam = Broodcock::factory()->female()->create();
        $record = BreedingRecord::factory()->forPair($sire, $dam)->withUnregisteredOffspring(1)->create();

        $chick = $this->action()->handle($record, 1)->first();

        // The whole point: the chick's family tree is populated without anyone
        // typing the parents in by hand.
        $this->assertTrue($chick->sire->is($sire));
        $this->assertTrue($chick->dam->is($dam));
        $this->assertTrue($sire->offspringAsSire()->get()->contains($chick));
        $this->assertTrue($dam->offspringAsDam()->get()->contains($chick));
    }

    public function test_you_cannot_register_more_chicks_than_hatched(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(2)->create();

        $this->expectException(RuntimeException::class);

        $this->action()->handle($record, 3);
    }

    /**
     * If the count check fails, NOTHING may be written - not the birds, not
     * the incremented counter. This is what the DB::transaction() is for.
     */
    public function test_an_over_registration_attempt_writes_nothing_at_all(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(2)->create();
        $broodcocksBefore = Broodcock::count();

        try {
            $this->action()->handle($record, 99);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame($broodcocksBefore, Broodcock::count(), 'Birds were created despite the failure.');
        $this->assertSame(0, $record->fresh()->offspring_count, 'The counter moved despite the failure.');
    }

    public function test_registering_zero_or_fewer_is_rejected(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(3)->create();

        $this->expectException(RuntimeException::class);

        $this->action()->handle($record, 0);
    }

    public function test_a_fully_registered_hatch_offers_nothing_more_to_register(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(3)->create();

        $this->action()->handle($record, 3);
        $record->refresh();

        $this->assertSame(0, $record->unregisteredOffspring());
        $this->assertFalse($record->hasUnregisteredOffspring());
    }

    // ---------------------------------------------------------------
    // Authorization
    // ---------------------------------------------------------------

    public function test_a_record_keeper_can_generate_offspring(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(2)->create();

        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(Show::class, ['record' => $record])
            ->call('startGenerating')
            ->set('generateCount', 2)
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertSame(2, $record->fresh()->offspring_count);
    }

    public function test_a_customer_cannot_even_view_a_breeding_record(): void
    {
        $record = BreedingRecord::factory()->create();

        $this->actingAs(User::factory()->customer()->create());

        Livewire::test(Show::class, ['record' => $record])->assertForbidden();
    }

    public function test_a_customer_cannot_reach_the_breeding_list(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('breeding.index'))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Computed rates - never stored, never hand-entered
    // ---------------------------------------------------------------

    public function test_fertility_and_hatch_rates_are_computed_from_the_egg_counts(): void
    {
        $record = BreedingRecord::factory()->create([
            'eggs_set' => 20,
            'eggs_fertile' => 15,
            'eggs_hatched' => 12,
            'offspring_count' => 0,
        ]);

        $this->assertSame(75.0, $record->fertilityRate());   // 15/20
        $this->assertSame(80.0, $record->hatchRate());       // 12/15
        $this->assertSame(60.0, $record->overallHatchRate()); // 12/20
    }

    public function test_rates_are_null_rather_than_zero_when_there_is_no_data(): void
    {
        $record = BreedingRecord::factory()->create([
            'eggs_set' => 0,
            'eggs_fertile' => 0,
            'eggs_hatched' => 0,
            'offspring_count' => 0,
        ]);

        // Null means "no data", which is different from a genuine 0% rate.
        $this->assertNull($record->fertilityRate());
        $this->assertNull($record->hatchRate());
    }

    public function test_there_is_no_stored_rate_column_to_contradict_the_counts(): void
    {
        // Guards the design decision: rates must stay derived. If someone adds
        // a fertility_rate column later, this fails and forces the discussion.
        $this->assertFalse(
            Schema::hasColumn('breeding_records', 'fertility_rate'),
            'fertility_rate must remain a computed accessor, not a stored column.'
        );
        $this->assertFalse(
            Schema::hasColumn('breeding_records', 'hatch_rate'),
            'hatch_rate must remain a computed accessor, not a stored column.'
        );
    }
}
