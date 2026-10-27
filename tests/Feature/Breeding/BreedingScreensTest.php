<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Livewire\Breeding\Index;
use App\Livewire\Breeding\Show;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The breeding list and a single mating, as a keeper reads them: the pair by
 * name and band, each bird reachable from the row, and the count the screen
 * opens with taken from the records themselves.
 */
final class BreedingScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_shows_each_pair_with_bands_and_links_to_both_birds(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Bagwis', 'band_number' => 'GF-1001']);
        $dam = Broodcock::factory()->female()->unbanded()->create(['name' => 'Ilocos Hen']);
        BreedingRecord::factory()->forPair($sire, $dam)->create([
            'mating_date' => '2026-05-04',
            'eggs_set' => 12,
            'eggs_fertile' => 10,
            'eggs_hatched' => 8,
        ]);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertSee('Breeding Records')
            ->assertSee('mating recorded')
            ->assertSee('Bagwis')
            ->assertSee('GF-1001')
            ->assertSee('Ilocos Hen')
            ->assertSee('Not yet banded')
            ->assertSee('4 May 2026')
            ->assertSee('80%')
            ->assertSeeHtml('href="'.route('broodcocks.show', $sire).'"')
            ->assertSeeHtml('href="'.route('broodcocks.show', $dam).'"');
    }

    public function test_the_opening_figure_follows_the_search(): void
    {
        BreedingRecord::factory()->count(2)->create();
        $sire = Broodcock::factory()->male()->create(['name' => 'Kelso Prime']);
        BreedingRecord::factory()->forPair($sire, Broodcock::factory()->female()->create())->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertSee('matings recorded')
            ->set('search', 'Kelso Prime')
            ->assertSee('mating matches your filters')
            ->assertDontSee('matings recorded');
    }

    public function test_an_empty_list_says_what_to_do_next(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertSee('No breeding records yet')
            ->assertSee('Record your first mating');
    }

    public function test_a_mating_shows_the_pair_the_hatch_and_its_chicks(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Bagwis', 'band_number' => 'GF-1001']);
        $dam = Broodcock::factory()->female()->create(['name' => 'Ilocos Hen', 'band_number' => 'GF-2002']);
        $record = BreedingRecord::factory()->forPair($sire, $dam)->create([
            'eggs_set' => 12,
            'eggs_fertile' => 10,
            'eggs_hatched' => 8,
            'offspring_count' => 1,
        ]);
        Broodcock::factory()->bredFrom($sire, $dam)->create(['name' => 'First Chick']);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['record' => $record])
            ->assertSee('Bagwis')
            ->assertSee('GF-1001')
            ->assertSee('GF-2002')
            ->assertSee('The hatch')
            ->assertSee('83.3%')
            ->assertSee('80.0%')
            ->assertSee('First Chick')
            ->assertSeeHtml('href="'.route('broodcocks.show', $dam).'"');
    }

    public function test_a_mating_with_no_chicks_states_it(): void
    {
        $record = BreedingRecord::factory()->create(['eggs_set' => 0, 'eggs_fertile' => 0, 'eggs_hatched' => 0]);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['record' => $record])
            ->assertSee('No chicks registered yet')
            ->assertSee('Nothing hatched from this mating.');
    }
}
