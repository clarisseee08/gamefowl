<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Catalog\Browse;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The catalogue's read cache.
 *
 * MEASURED, NOT ASSUMED. Against the production database every query costs
 * ~240ms of round trip regardless of how trivial it is - a count(*) over four
 * rows measured 230ms - and opening the connection costs a further ~700ms. The
 * catalogue ran four queries, so the page spent ~2.2 seconds doing almost no
 * work. Caching the results is worth far more here than any query tuning.
 *
 * THE RISK IS STALENESS, NOT SPEED, so most of this file is about invalidation.
 * A catalogue that is fast and wrong is worse than one that is slow and right:
 * it advertises birds the farm has already sold.
 *
 * Note what these tests do NOT do: assert a total query count. The number of
 * queries a Livewire render makes depends on auth and session plumbing that is
 * not this feature's business. They count queries against the broodcock tables
 * specifically, which is the thing the cache is meant to eliminate.
 */
final class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    /** Count queries touching the broodcock tables during one catalogue render. */
    private function birdQueriesForOneRender(User $viewer): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($viewer)->test(Browse::class)->html();

        $queries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'broodcock'))
            ->count();

        DB::disableQueryLog();

        return $queries;
    }

    public function test_a_repeat_catalogue_render_does_not_touch_the_broodcock_tables(): void
    {
        $customer = User::factory()->customer()->create();
        Broodcock::factory()->count(3)->create();

        $cold = $this->birdQueriesForOneRender($customer);
        $warm = $this->birdQueriesForOneRender($customer);

        $this->assertGreaterThan(0, $cold, 'The first render should read the database.');
        $this->assertSame(0, $warm, 'The second render should be served entirely from cache.');
    }

    // -----------------------------------------------------------------
    // Invalidation.
    //
    // The cache is keyed by a version counter that every write to a bird or a
    // bird photo bumps, because the `file` cache store Render uses does not
    // support tags. Each of these tests fails against a cache that is never
    // invalidated.
    // -----------------------------------------------------------------

    public function test_a_newly_registered_bird_appears_in_an_already_cached_catalogue(): void
    {
        $customer = User::factory()->customer()->create();
        Broodcock::factory()->create(['name' => 'Old Timer']);

        Livewire::actingAs($customer)->test(Browse::class)->assertSee('Old Timer');

        Broodcock::factory()->create(['name' => 'New Arrival']);

        Livewire::actingAs($customer)->test(Browse::class)
            ->assertSee('New Arrival')
            ->assertSee('Old Timer');
    }

    public function test_renaming_a_bird_updates_an_already_cached_catalogue(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create(['name' => 'Wrong Name']);

        Livewire::actingAs($customer)->test(Browse::class)->assertSee('Wrong Name');

        $bird->update(['name' => 'Corrected Name']);

        Livewire::actingAs($customer)->test(Browse::class)
            ->assertSee('Corrected Name')
            ->assertDontSee('Wrong Name');
    }

    public function test_removing_a_bird_drops_it_from_an_already_cached_catalogue(): void
    {
        $customer = User::factory()->customer()->create();
        $bird = Broodcock::factory()->create(['name' => 'Departed Bird']);
        Broodcock::factory()->create(['name' => 'Remaining Bird']);

        Livewire::actingAs($customer)->test(Browse::class)->assertSee('Departed Bird');

        $bird->delete();

        Livewire::actingAs($customer)->test(Browse::class)
            ->assertSee('Remaining Bird')
            ->assertDontSee('Departed Bird');
    }

    public function test_a_new_bloodline_appears_in_the_cached_filter_options(): void
    {
        $customer = User::factory()->customer()->create();
        Broodcock::factory()->create(['bloodline' => 'Sweater']);

        Livewire::actingAs($customer)->test(Browse::class)->assertSee('Sweater');

        Broodcock::factory()->create(['bloodline' => 'Hatch']);

        Livewire::actingAs($customer)->test(Browse::class)->assertSee('Hatch');
    }

    /**
     * A filtered view must not be served the unfiltered page, and vice versa.
     *
     * The obvious way to get this wrong is a single cache key for "the
     * catalogue", which would hand a customer who searched for one bloodline
     * whatever the previous visitor happened to look at.
     */
    public function test_a_filtered_catalogue_is_cached_separately_from_the_full_one(): void
    {
        $customer = User::factory()->customer()->create();
        Broodcock::factory()->create(['name' => 'Sweaterbird', 'bloodline' => 'Sweater']);
        Broodcock::factory()->create(['name' => 'Kelsobird', 'bloodline' => 'Kelso']);

        Livewire::actingAs($customer)->test(Browse::class)
            ->assertSee('Sweaterbird')
            ->assertSee('Kelsobird');

        Livewire::actingAs($customer)->test(Browse::class)
            ->set('bloodline', 'Sweater')
            ->assertSee('Sweaterbird')
            ->assertDontSee('Kelsobird');
    }
}
