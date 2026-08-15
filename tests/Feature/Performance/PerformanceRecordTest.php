<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Livewire\Performance\Index;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

final class PerformanceRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoutesIfMissing();
    }

    // -----------------------------------------------------------------
    // Listing, searching and filtering
    // -----------------------------------------------------------------

    public function test_the_performance_list_shows_records_and_paginates(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Kanlaon']);
        PerformanceRecord::factory()->count(20)->for($bird)->create();

        $component = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertOk()
            ->assertSee('Kanlaon');

        // config('gfms.per_page') is 15, so 20 records must split over pages.
        $this->assertSame(20, $component->instance()->records->total());
        $this->assertSame(15, $component->instance()->records->count());
    }

    public function test_the_list_can_be_searched_by_bird(): void
    {
        $wanted = Broodcock::factory()->create(['name' => 'Kanlaon', 'band_number' => 'KN-0001']);
        $other = Broodcock::factory()->create(['name' => 'Mayon', 'band_number' => 'MY-0002']);

        PerformanceRecord::factory()->for($wanted)->create();
        PerformanceRecord::factory()->for($other)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->set('search', 'Kanlaon')
            ->assertSee('Kanlaon')
            ->assertDontSee('Mayon');
    }

    public function test_the_list_can_be_filtered_by_event_type_and_result(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Kanlaon']);

        PerformanceRecord::factory()->derby()->win()->for($bird)->create();
        PerformanceRecord::factory()->sparring()->loss()->for($bird)->create();
        PerformanceRecord::factory()->conditioning()->for($bird)->create();

        $component = Livewire::actingAs(User::factory()->staff()->create())->test(Index::class);

        $this->assertSame(3, $component->instance()->records->total());

        $component->set('eventType', PerformanceEventType::Derby->value);
        $this->assertSame(1, $component->instance()->records->total());

        $component->set('eventType', '')->set('result', PerformanceResult::Loss->value);
        $this->assertSame(1, $component->instance()->records->total());
    }

    public function test_the_list_can_be_filtered_by_a_date_range(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->on('2026-01-10')->for($bird)->create();
        PerformanceRecord::factory()->on('2026-03-15')->for($bird)->create();
        PerformanceRecord::factory()->on('2026-06-20')->for($bird)->create();

        $component = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->set('from', '2026-02-01')
            ->set('to', '2026-05-01');

        $this->assertSame(1, $component->instance()->records->total());
    }

    public function test_changing_a_filter_returns_to_the_first_page(): void
    {
        $bird = Broodcock::factory()->create();
        PerformanceRecord::factory()->count(20)->for($bird)->create();

        $component = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->call('gotoPage', 2);

        $this->assertSame(2, $component->instance()->records->currentPage());

        // Without resetPage() the user would land on a page that no longer exists.
        $component->set('eventType', PerformanceEventType::Derby->value);

        $this->assertSame(1, $component->instance()->records->currentPage());
    }

    /**
     * Test-local route table. These are the routes this module needs; the
     * real ones live in routes/web.php, which this module does not own.
     */
    private function registerRoutesIfMissing(): void
    {
        if (! Route::has('performance.index')) {
            Route::get('/performance', fn () => '')->name('performance.index');
            Route::get('/performance/create', fn () => '')->name('performance.create');
            Route::get('/performance/{performance_record}/edit', fn () => '')->name('performance.edit');
        }

        if (! Route::has('broodcocks.show')) {
            Route::get('/broodcocks/{broodcock}', fn () => '')->name('broodcocks.show');
        }
    }
}
