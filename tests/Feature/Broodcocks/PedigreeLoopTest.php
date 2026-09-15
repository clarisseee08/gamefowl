<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Enums\Sex;
use App\Livewire\Broodcocks\Form;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A bird cannot be its own ancestor.
 *
 * sire_id and dam_id are self-referencing foreign keys, so nothing in the
 * schema stops a loop. The PostgreSQL CHECK constraints added with the table
 * catch only the one-step case - a bird that is literally its own parent - and
 * the migration that added them states that deeper cycles are "prevented in the
 * application layer, where the full ancestor chain is known". That was never
 * true until these rules existed.
 *
 * The damage is not a crash. Pedigree walks a fixed three generations and stops
 * cleanly, so a cycle renders quietly as a bird being its own grandfather - on
 * the one screen this system exists to justify.
 */
final class PedigreeLoopTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    /**
     * Fills the edit form from the bird, so a save exercises the real rules
     * rather than a hand-built payload that skips the required fields.
     */
    private function editing(Broodcock $bird): Testable
    {
        return Livewire::actingAs($this->staff())->test(Form::class, ['broodcock' => $bird]);
    }

    // -----------------------------------------------------------------
    // The cases the database already caught
    // -----------------------------------------------------------------

    public function test_a_bird_cannot_be_its_own_sire(): void
    {
        $bird = Broodcock::factory()->male()->create();

        $this->editing($bird)
            ->set('sire_id', (string) $bird->id)
            ->call('save')
            ->assertHasErrors('sire_id');
    }

    // -----------------------------------------------------------------
    // The cases nothing caught
    // -----------------------------------------------------------------

    /** The two-step loop: A is B's sire, so B cannot be A's sire. */
    public function test_a_bird_cannot_take_its_own_son_as_its_sire(): void
    {
        $father = Broodcock::factory()->male()->create(['name' => 'Agila']);
        $son = Broodcock::factory()->male()->create(['name' => 'Kidlat', 'sire_id' => $father->id]);

        $this->editing($father)
            ->set('sire_id', (string) $son->id)
            ->call('save')
            ->assertHasErrors('sire_id');

        $this->assertNull($father->fresh()->sire_id);
    }

    /** And the same on the female side. */
    public function test_a_hen_cannot_take_her_own_daughter_as_her_dam(): void
    {
        $mother = Broodcock::factory()->female()->create(['name' => 'Bituin']);
        $daughter = Broodcock::factory()->female()->create(['name' => 'Diwata', 'dam_id' => $mother->id]);

        $this->editing($mother)
            ->set('dam_id', (string) $daughter->id)
            ->call('save')
            ->assertHasErrors('dam_id');
    }

    /**
     * Three generations deep, which is DEEPER THAN THE CHART NOW DRAWS.
     *
     * That gap is the point. The pedigree renders one generation, but the loop
     * guard walks the whole ancestry, and it has to: a cycle three links up is
     * still a cycle, and the code that follows sire_id - the breeding records,
     * the offspring generator, the guard itself - would recurse forever on it
     * whether or not any screen draws that far. Cutting the check back to the
     * render depth would make an infinite loop reachable again.
     */
    public function test_a_bird_cannot_take_its_own_grandson_as_its_sire(): void
    {
        $grandfather = Broodcock::factory()->male()->create();
        $father = Broodcock::factory()->male()->create(['sire_id' => $grandfather->id]);
        $grandson = Broodcock::factory()->male()->create(['sire_id' => $father->id]);

        $this->editing($grandfather)
            ->set('sire_id', (string) $grandson->id)
            ->call('save')
            ->assertHasErrors('sire_id');
    }

    /** A descendant reached through the DAM side still counts as a descendant. */
    public function test_the_check_follows_both_parents_not_just_the_sire_line(): void
    {
        $ancestor = Broodcock::factory()->male()->create();
        $daughter = Broodcock::factory()->female()->create(['sire_id' => $ancestor->id]);
        $grandson = Broodcock::factory()->male()->create(['dam_id' => $daughter->id]);

        $this->editing($ancestor)
            ->set('sire_id', (string) $grandson->id)
            ->call('save')
            ->assertHasErrors('sire_id');
    }

    // -----------------------------------------------------------------
    // What must still be allowed
    // -----------------------------------------------------------------

    /**
     * The rule must not refuse ordinary line breeding.
     *
     * A cock mated back to an unrelated hen, or to a bird that merely SHARES an
     * ancestor, is normal practice on a breeding farm - only true descent is a
     * loop. A rule that blocked half-siblings would be worse than no rule,
     * because staff would route around it.
     */
    public function test_an_unrelated_bird_is_still_an_acceptable_parent(): void
    {
        $bird = Broodcock::factory()->create(['sex' => Sex::Female]);
        $sire = Broodcock::factory()->male()->create();

        $this->editing($bird)
            ->set('sire_id', (string) $sire->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($sire->id, $bird->fresh()->sire_id);
    }

    public function test_two_birds_sharing_an_ancestor_may_still_be_paired(): void
    {
        $commonSire = Broodcock::factory()->male()->create();
        $hen = Broodcock::factory()->female()->create(['sire_id' => $commonSire->id]);
        $halfBrother = Broodcock::factory()->male()->create(['sire_id' => $commonSire->id]);

        $this->editing($hen)
            ->set('sire_id', (string) $halfBrother->id)
            ->call('save')
            ->assertHasNoErrors();
    }

    /** Creating a bird cannot make a loop - nothing descends from it yet. */
    public function test_adding_a_new_bird_is_unaffected(): void
    {
        $sire = Broodcock::factory()->male()->create();
        $dam = Broodcock::factory()->female()->create();

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('name', 'Bagwis')
            ->set('sire_id', (string) $sire->id)
            ->set('dam_id', (string) $dam->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', ['name' => 'Bagwis', 'sire_id' => $sire->id]);
    }

    /**
     * A cycle written before this rule existed must not hang the form.
     *
     * Walking an ancestor chain that loops never terminates without a visited
     * set - and opening the edit form is the first thing anyone would do on
     * finding a cycle in their data.
     */
    public function test_an_existing_cycle_does_not_hang_the_validator(): void
    {
        $a = Broodcock::factory()->male()->create();
        $b = Broodcock::factory()->male()->create(['sire_id' => $a->id]);

        // Forced straight into the database, bypassing validation, to
        // reproduce data that predates the rule.
        Broodcock::withoutEvents(fn () => Broodcock::query()->whereKey($a->id)->update(['sire_id' => $b->id]));

        $unrelated = Broodcock::factory()->male()->create();

        $this->editing($unrelated->fresh())
            ->set('sire_id', (string) $a->id)
            ->call('save')
            ->assertHasNoErrors();
    }
}
