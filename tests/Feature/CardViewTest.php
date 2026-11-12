<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every list in the console reads as a table or as cards (EMS DataTable /
 * DataGrid). The switch is Alpine and remembered per list; the cards are
 * rendered beside the table from the same rows, so switching never waits on
 * the server.
 */
final class CardViewTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, callable(): void}> */
    public static function lists(): array
    {
        return [
            'gamefowl' => ['broodcocks.index', 'gamefowl', fn () => Broodcock::factory()->create()],
            'breeding' => ['breeding.index', 'breeding', fn () => BreedingRecord::factory()->create()],
            'health' => ['health.index', 'health', fn () => HealthRecord::factory()->create()],
            'performance' => ['performance.index', 'performance', fn () => PerformanceRecord::factory()->create()],
            'mortality' => ['mortality.index', 'mortality', fn () => MortalityRecord::factory()->create()],
            'users' => ['users.index', 'users', fn () => null],
            'archive' => ['archive.index', 'archive', fn () => Broodcock::factory()->create()->delete()],
        ];
    }

    #[DataProvider('lists')]
    public function test_the_list_offers_table_and_cards_and_remembers_the_choice(string $route, string $key, callable $seed): void
    {
        $seed();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route($route))
            ->assertOk()
            ->assertSee('aria-label="Table"', false)
            ->assertSee('aria-label="Cards"', false)
            ->assertSee("\$persist('table').as('gfms-view-{$key}')", false);
    }

    #[DataProvider('lists')]
    public function test_each_row_also_renders_as_a_card(string $route, string $key, callable $seed): void
    {
        $seed();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route($route))
            ->assertOk()
            ->assertSee('data-record-card', false);
    }
}
