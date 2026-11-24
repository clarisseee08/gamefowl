<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Enums\Relatedness;
use App\Livewire\Breeding\Form;
use App\Livewire\Broodcocks\Pedigree;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use App\Support\Inbreeding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The inbreeding check: Wright's coefficient of inbreeding from the recorded
 * family tree, on Record a mating and on every bird's family tree.
 *
 * The figures are the textbook ones (Wright 1922; Bourdon, Understanding Animal
 * Breeding): a chick of full siblings or of a parent and its offspring is 25%
 * inbred, of half-siblings 12.5%, of first cousins 6.25%. If the recursion is
 * wrong, one of these is the first thing to come out different.
 */
final class InbreedingCheckTest extends TestCase
{
    use RefreshDatabase;

    private function founder(bool $male = true): Broodcock
    {
        return ($male ? Broodcock::factory()->male() : Broodcock::factory()->female())->create();
    }

    private function chickOf(Broodcock $sire, Broodcock $dam, bool $male = true): Broodcock
    {
        return ($male ? Broodcock::factory()->male() : Broodcock::factory()->female())
            ->create(['sire_id' => $sire->id, 'dam_id' => $dam->id]);
    }

    public function test_unrelated_birds_give_zero(): void
    {
        $this->assertSame(0.0, Inbreeding::ofOffspring($this->founder()->id, $this->founder(false)->id));
    }

    public function test_full_siblings_give_twenty_five_percent(): void
    {
        [$sire, $dam] = [$this->founder(), $this->founder(false)];

        $brother = $this->chickOf($sire, $dam);
        $sister = $this->chickOf($sire, $dam, male: false);

        $this->assertEqualsWithDelta(0.25, Inbreeding::ofOffspring($brother->id, $sister->id), 1e-9);
    }

    public function test_a_parent_with_its_own_offspring_gives_twenty_five_percent(): void
    {
        $sire = $this->founder();
        $daughter = $this->chickOf($sire, $this->founder(false), male: false);

        $this->assertEqualsWithDelta(0.25, Inbreeding::ofOffspring($sire->id, $daughter->id), 1e-9);
    }

    public function test_half_siblings_give_twelve_and_a_half_percent(): void
    {
        $sire = $this->founder();

        $son = $this->chickOf($sire, $this->founder(false));
        $daughter = $this->chickOf($sire, $this->founder(false), male: false);

        $this->assertEqualsWithDelta(0.125, Inbreeding::ofOffspring($son->id, $daughter->id), 1e-9);
    }

    public function test_first_cousins_give_six_and_a_quarter_percent(): void
    {
        [$grandsire, $granddam] = [$this->founder(), $this->founder(false)];

        $uncle = $this->chickOf($grandsire, $granddam);
        $aunt = $this->chickOf($grandsire, $granddam, male: false);

        $cousinA = $this->chickOf($uncle, $this->founder(false));
        $cousinB = $this->chickOf($this->founder(), $aunt, male: false);

        $this->assertEqualsWithDelta(0.0625, Inbreeding::ofOffspring($cousinA->id, $cousinB->id), 1e-9);
    }

    public function test_an_inbred_common_ancestor_raises_the_figure(): void
    {
        // The shared sire is himself a full-sibling mating (COI 25%), so his
        // half-sibling grandchildren's chicks are 1/8 x (1 + 1/4) = 15.625%.
        [$a, $b] = [$this->founder(), $this->founder(false)];
        $inbredSire = $this->chickOf($this->chickOf($a, $b), $this->chickOf($a, $b, male: false));

        $son = $this->chickOf($inbredSire, $this->founder(false));
        $daughter = $this->chickOf($inbredSire, $this->founder(false), male: false);

        $this->assertEqualsWithDelta(0.25, Inbreeding::ofBird($inbredSire), 1e-9);
        $this->assertEqualsWithDelta(0.15625, Inbreeding::ofOffspring($son->id, $daughter->id), 1e-9);
    }

    public function test_an_archived_ancestor_still_counts(): void
    {
        $sire = $this->founder();
        $son = $this->chickOf($sire, $this->founder(false));
        $daughter = $this->chickOf($sire, $this->founder(false), male: false);

        $sire->delete();

        $this->assertEqualsWithDelta(0.125, Inbreeding::ofOffspring($son->id, $daughter->id), 1e-9);
    }

    public function test_nothing_is_claimed_when_a_parent_is_missing(): void
    {
        $this->assertNull(Inbreeding::ofOffspring($this->founder()->id, null));
        $this->assertNull(Inbreeding::ofBird($this->founder()));
    }

    public function test_a_loop_in_the_data_does_not_hang_the_page(): void
    {
        $a = $this->founder();
        $b = $this->chickOf($a, $this->founder(false));

        // Written straight to the table, past the form's ancestor-loop rule.
        DB::table('broodcocks')->where('id', $a->id)->update(['sire_id' => $b->id]);

        $this->assertIsFloat(Inbreeding::ofOffspring($a->id, $b->id));
    }

    public function test_a_value_cut_short_by_a_loop_is_never_cached(): void
    {
        // A two-bird loop written straight to the table, past the forms.
        $a = $this->founder();
        $b = $this->chickOf($a, $this->founder(false));
        DB::table('broodcocks')->where('id', $a->id)->update(['sire_id' => $b->id]);

        $calculation = new Inbreeding;
        $load = new \ReflectionMethod($calculation, 'load');
        $load->invoke($calculation, [$a->id, $b->id]);
        (new \ReflectionMethod($calculation, 'kinshipOf'))->invoke($calculation, $a->id, $b->id);

        $truncations = (new \ReflectionProperty($calculation, 'truncations'))->getValue($calculation);
        $cache = (new \ReflectionProperty($calculation, 'kinship'))->getValue($calculation);

        $this->assertGreaterThan(0, $truncations, 'The loop should have reached the depth cap.');
        $this->assertArrayNotHasKey(min($a->id, $b->id).':'.max($a->id, $b->id), $cache);
    }

    public function test_the_cost_is_one_query_per_generation_however_wide_the_tree(): void
    {
        $sire = $this->founder();
        $dam = $this->founder(false);

        for ($generation = 0; $generation < 10; $generation++) {
            $sire = $this->chickOf($sire, $this->founder(false));
            $dam = $this->chickOf($this->founder(), $dam, male: false);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        Inbreeding::ofOffspring($sire->id, $dam->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(Inbreeding::GENERATIONS + 1, $queries);
    }

    public function test_percentages_read_the_way_breeders_write_them(): void
    {
        $this->assertSame('0%', Inbreeding::percent(0.0));
        $this->assertSame('25%', Inbreeding::percent(0.25));
        $this->assertSame('12.5%', Inbreeding::percent(0.125));
        $this->assertSame('3.13%', Inbreeding::percent(0.03125));
    }

    public function test_the_levels_follow_the_familiar_relationships(): void
    {
        $this->assertSame(Relatedness::Unrelated, Relatedness::fromCoefficient(0.0));
        $this->assertSame(Relatedness::Distant, Relatedness::fromCoefficient(0.03125));
        $this->assertSame(Relatedness::Cousins, Relatedness::fromCoefficient(0.0625));
        $this->assertSame(Relatedness::HalfSiblings, Relatedness::fromCoefficient(0.125));
        $this->assertSame(Relatedness::Close, Relatedness::fromCoefficient(0.25));
    }

    public function test_record_a_mating_shows_the_check_for_a_related_pair_and_still_saves(): void
    {
        [$sire, $dam] = [$this->founder(), $this->founder(false)];
        $brother = $this->chickOf($sire, $dam);
        $sister = $this->chickOf($sire, $dam, male: false);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->assertDontSee('Inbreeding Check')
            ->set('sire_choice', (string) $brother->id)
            ->assertDontSee('Inbreeding Check')
            ->set('dam_choice', (string) $sister->id)
            ->assertSee('Inbreeding Check')
            ->assertSee('25%')
            ->assertSee('Very closely related')
            ->assertSee('It does not stop the mating from being saved.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, BreedingRecord::query()->count());
    }

    public function test_an_unrelated_pair_is_said_to_share_no_recorded_ancestors(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('sire_choice', (string) $this->founder()->id)
            ->set('dam_choice', (string) $this->founder(false)->id)
            ->assertSee('No shared ancestors on record')
            ->assertSee('0%');
    }

    public function test_an_outside_parent_typed_by_name_gets_no_check(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('sire_choice', (string) $this->founder()->id)
            ->set('dam_choice', Form::OFF_LIST)
            ->assertDontSee('Inbreeding Check');
    }

    public function test_the_family_tree_shows_the_birds_own_coefficient_after_it_paints(): void
    {
        [$sire, $dam] = [$this->founder(), $this->founder(false)];
        $inbred = $this->chickOf($this->chickOf($sire, $dam), $this->chickOf($sire, $dam, male: false));

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Pedigree::class, ['broodcock' => $inbred])
            ->assertDontSee('Coefficient of Inbreeding (COI):')
            ->call('loadInbreeding')
            ->assertSee('Coefficient of Inbreeding (COI):')
            ->assertSee('25%')
            ->assertSee('Very closely related');
    }

    public function test_the_family_tree_says_when_it_cannot_be_worked_out(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Pedigree::class, ['broodcock' => $this->founder()])
            ->call('loadInbreeding')
            ->assertSee('cannot be worked out until both the sire and the dam are recorded');
    }
}
