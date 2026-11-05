<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Index;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The gamefowl list pages in 10, 25, 50 or 100 rows, chosen in the footer
 * under the table - which is also where "Page 1 of 3" now lives, not above
 * the rows.
 */
final class BroodcockListPagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_shows_ten_rows_by_default_and_says_where_you_are_under_the_table(): void
    {
        Broodcock::factory()->count(30)->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->assertSet('perPage', 10);

        $this->assertCount(10, $component->instance()->broodcocks->items());

        $html = $component->html();
        $this->assertStringContainsString('Rows per page', $html);
        $this->assertMatchesRegularExpression('/Page <span[^>]*>1<\/span> of <span[^>]*>3<\/span>/', $html);
        $this->assertGreaterThan(strpos($html, '<table'), strpos($html, 'Rows per page'),
            'The page position belongs in the footer under the table, not above it.');
    }

    public function test_choosing_a_page_size_changes_the_rows_and_returns_to_page_one(): void
    {
        Broodcock::factory()->count(30)->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('nextPage', 'page')
            ->set('perPage', 25);

        $birds = $component->instance()->broodcocks;
        $this->assertCount(25, $birds->items());
        $this->assertSame(1, $birds->currentPage());
        $this->assertSame(2, $birds->lastPage());
    }

    public function test_a_page_size_that_is_not_offered_falls_back_to_ten(): void
    {
        Broodcock::factory()->count(30)->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->withQueryParams(['per' => 1000])
            ->test(Index::class);

        $this->assertCount(10, $component->instance()->broodcocks->items());
    }

    public function test_every_offered_page_size_is_in_the_picker(): void
    {
        Broodcock::factory()->count(3)->create();

        $html = Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->html();

        foreach (Index::PAGE_SIZES as $size) {
            $this->assertStringContainsString('<option value="'.$size.'">'.$size.'</option>', $html);
        }
    }

    public function test_hovering_a_row_previews_the_bird_without_a_photo_saying_so(): void
    {
        Broodcock::factory()->create(['name' => 'Dalisay']);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->assertSee('x-data="{ preview: false', escape: false)
            ->assertSee('No photo yet');
    }
}
