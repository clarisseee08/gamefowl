<?php

declare(strict_types=1);

namespace Tests\Feature\Mortality;

use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Livewire\Mortality\Form;
use App\Livewire\Mortality\Index;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The mortality register and the form that feeds it.
 *
 * Atomicity of the two-table write lives in MortalityTransactionTest; role
 * access lives in MortalityAuthorizationTest. This file covers listing,
 * filtering, the derived summary, and validation.
 */
final class MortalityRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // routes/web.php is owned by another vertical. Registering the routes
        // here only if they are missing keeps this suite self-contained now and
        // silently defers to the real ones once they exist.
        if (! Route::has('mortality.index')) {
            Route::middleware(['web', 'auth', 'active'])->group(function (): void {
                Route::livewire('/mortality', Index::class)->name('mortality.index');
                Route::livewire('/mortality/create/{broodcock?}', Form::class)->name('mortality.create');
            });

            // Names are indexed when the application boots, which already
            // happened above - re-index so route() can find these.
            Route::getRoutes()->refreshNameLookups();
        }
    }

    // -----------------------------------------------------------------
    // Listing
    // -----------------------------------------------------------------

    public function test_the_register_shows_the_bird_the_cause_and_who_recorded_it(): void
    {
        $staff = User::factory()->staff()->create(['full_name' => 'Maria Santos']);
        $bird = $this->makeBroodcock(['name' => 'Kalabaw', 'band_number' => 'GF-0001']);

        MortalityRecord::factory()
            ->forBroodcock($bird)
            ->recordedBy($staff)
            ->create([
                'date_of_death' => '2026-07-04',
                'cause_of_death' => 'Predator attack',
                'disposal_method' => 'Buried',
            ]);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertOk()
            ->assertSee('Kalabaw')
            ->assertSee('GF-0001')
            ->assertSee('04 Jul 2026')
            ->assertSee('Predator attack')
            ->assertSee('Buried')
            ->assertSee('Maria Santos');
    }

    /** Age at death is derived from date_hatched - it is never a column. */
    public function test_the_register_shows_the_age_at_death(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['date_hatched' => '2024-01-10']);

        MortalityRecord::factory()->forBroodcock($bird)->recordedBy($staff)
            ->create(['date_of_death' => '2026-07-10']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertSee('2 years');
    }

    public function test_the_register_is_paginated(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (range(1, 20) as $ignored) {
            MortalityRecord::factory()
                ->forBroodcock($this->makeBroodcock())
                ->recordedBy($staff)
                ->create();
        }

        $component = Livewire::actingAs($staff)->test(Index::class)->assertOk();

        $this->assertCount(15, $component->instance()->rows->items());
        $this->assertSame(20, $component->instance()->rows->total());
        $this->assertSame(2, $component->instance()->rows->lastPage());
    }

    /**
     * Supabase is a network hop away, so an N+1 in a 15-row list is 30 extra
     * round trips. Model::shouldBeStrict() would already throw on a lazy load;
     * this pins the query count as well.
     */
    public function test_the_register_does_not_run_a_query_per_row(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (range(1, 12) as $ignored) {
            MortalityRecord::factory()
                ->forBroodcock($this->makeBroodcock())
                ->recordedBy($staff)
                ->create();
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::actingAs($staff)->test(Index::class)->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Rows + count + summary + breakdown + cause options, plus the session
        // user lookup. Nowhere near one-per-row.
        $this->assertLessThan(12, $queries, "Expected a bounded query count, ran {$queries}.");
    }

    public function test_the_register_shows_an_empty_state_that_says_what_to_do_next(): void
    {
        $staff = User::factory()->staff()->create();

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->assertSee('No deaths have been recorded yet.')
            ->assertSee('Record a Death');
    }

    // -----------------------------------------------------------------
    // Filters
    // -----------------------------------------------------------------

    public function test_the_register_can_be_filtered_by_date_range(): void
    {
        $staff = User::factory()->staff()->create();

        MortalityRecord::factory()->forBroodcock($this->makeBroodcock(['name' => 'Early Bird']))
            ->recordedBy($staff)->create(['date_of_death' => '2026-01-05']);

        MortalityRecord::factory()->forBroodcock($this->makeBroodcock(['name' => 'Late Bird']))
            ->recordedBy($staff)->create(['date_of_death' => '2026-06-20']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->set('from', '2026-06-01')
            ->set('to', '2026-06-30')
            ->assertSee('Late Bird')
            ->assertDontSee('Early Bird');
    }

    public function test_the_register_can_be_filtered_by_cause_of_death(): void
    {
        $staff = User::factory()->staff()->create();

        MortalityRecord::factory()->forBroodcock($this->makeBroodcock(['name' => 'Sick Bird']))
            ->recordedBy($staff)->cause('Disease')->create();

        MortalityRecord::factory()->forBroodcock($this->makeBroodcock(['name' => 'Hurt Bird']))
            ->recordedBy($staff)->cause('Injury')->create();

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->set('cause', 'Disease')
            ->assertSee('Sick Bird')
            ->assertDontSee('Hurt Bird');
    }

    public function test_changing_a_filter_returns_to_the_first_page(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (range(1, 20) as $ignored) {
            MortalityRecord::factory()
                ->forBroodcock($this->makeBroodcock())
                ->recordedBy($staff)
                ->cause('Disease')
                ->create();
        }

        $component = Livewire::actingAs($staff)->test(Index::class);

        $component->call('gotoPage', 2);
        $this->assertSame(2, $component->instance()->rows->currentPage());

        // Without the resetPage() in updating(), the user would land on page 2
        // of a one-page result and think the filter found nothing.
        $component->set('cause', 'Disease');
        $this->assertSame(1, $component->instance()->rows->currentPage());
    }

    public function test_filters_can_be_cleared(): void
    {
        $staff = User::factory()->staff()->create();

        Livewire::actingAs($staff)
            ->test(Index::class)
            ->set('from', '2026-01-01')
            ->set('cause', 'Disease')
            ->call('clearFilters')
            ->assertSet('from', '')
            ->assertSet('cause', '');
    }

    // -----------------------------------------------------------------
    // Summary - computed, never stored
    // -----------------------------------------------------------------

    public function test_the_summary_counts_deaths_this_month_and_this_year(): void
    {
        Carbon::setTestNow('2026-08-16');

        $staff = User::factory()->staff()->create();

        // Two this month, one earlier this year, one last year.
        MortalityRecord::factory()->forBroodcock($this->makeBroodcock())->recordedBy($staff)
            ->create(['date_of_death' => '2026-08-02']);
        MortalityRecord::factory()->forBroodcock($this->makeBroodcock())->recordedBy($staff)
            ->create(['date_of_death' => '2026-08-14']);
        MortalityRecord::factory()->forBroodcock($this->makeBroodcock())->recordedBy($staff)
            ->create(['date_of_death' => '2026-03-01']);
        MortalityRecord::factory()->forBroodcock($this->makeBroodcock())->recordedBy($staff)
            ->create(['date_of_death' => '2025-12-30']);

        $summary = Livewire::actingAs($staff)->test(Index::class)->instance()->summary;

        $this->assertSame(2, $summary['this_month']);
        $this->assertSame(3, $summary['this_year']);
        $this->assertSame(4, $summary['total']);

        Carbon::setTestNow();
    }

    public function test_the_summary_breaks_deaths_down_by_cause(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (range(1, 3) as $ignored) {
            MortalityRecord::factory()->forBroodcock($this->makeBroodcock())
                ->recordedBy($staff)->cause('Disease')->create();
        }

        MortalityRecord::factory()->forBroodcock($this->makeBroodcock())
            ->recordedBy($staff)->cause('Injury')->create();

        $breakdown = Livewire::actingAs($staff)->test(Index::class)->instance()->causeBreakdown;

        $this->assertSame(['cause' => 'Disease', 'total' => 3], $breakdown->first());
        $this->assertSame(4, $breakdown->sum('total'));
    }

    // -----------------------------------------------------------------
    // Recording a death - the form
    // -----------------------------------------------------------------

    public function test_staff_can_record_a_death_and_the_bird_becomes_deceased(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw', 'date_hatched' => '2024-01-01']);

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2026-08-01')
            ->set('cause_of_death', 'Disease')
            ->set('disposal_method', 'Buried')
            ->set('remarks', 'Isolated from the flock two days before.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('mortality.index'));

        $this->assertDatabaseHas('mortality_records', [
            'broodcock_id' => $bird->id,
            'cause_of_death' => 'Disease',
            'disposal_method' => 'Buried',
            'recorded_by' => $staff->id,
        ]);

        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
    }

    /** The confirmation step names the bird before anything is written. */
    public function test_the_form_asks_for_confirmation_naming_the_bird_before_saving(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw', 'band_number' => 'GF-9001']);

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2026-08-01')
            ->set('cause_of_death', 'Disease')
            ->call('review')
            ->assertHasNoErrors()
            ->assertSet('confirming', true)
            ->assertSee('Record the death of Kalabaw (GF-9001)?');

        // Nothing is written until the confirmation is accepted.
        $this->assertDatabaseCount('mortality_records', 0);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    public function test_the_form_requires_a_bird_a_date_and_a_cause(): void
    {
        $staff = User::factory()->staff()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->call('save')
            ->assertHasErrors(['broodcock_id', 'date_of_death', 'cause_of_death']);

        $this->assertDatabaseCount('mortality_records', 0);
    }

    public function test_a_date_of_death_in_the_future_is_rejected(): void
    {
        Carbon::setTestNow('2026-08-16');

        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2026-08-17')
            ->set('cause_of_death', 'Disease')
            ->call('save')
            ->assertHasErrors(['date_of_death'])
            ->assertSee('The date of death cannot be in the future.');

        $this->assertDatabaseCount('mortality_records', 0);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_a_date_of_death_before_the_bird_hatched_is_rejected(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['date_hatched' => '2025-05-10']);

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2025-05-09')
            ->set('cause_of_death', 'Disease')
            ->call('save')
            ->assertHasErrors(['date_of_death'])
            ->assertSee('cannot be before the bird hatched on 10 May 2025');

        $this->assertDatabaseCount('mortality_records', 0);
    }

    /**
     * broodcock_id is UNIQUE, so a second death would be a database error.
     * Staff should read a sentence, not a 500 page.
     */
    public function test_a_bird_can_only_have_one_death_recorded(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock();

        MortalityRecord::factory()->forBroodcock($bird)->recordedBy($staff)->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', '2026-08-01')
            ->set('cause_of_death', 'Disease')
            ->call('save')
            ->assertHasErrors(['broodcock_id'])
            ->assertSee('A death has already been recorded for this bird.');

        $this->assertDatabaseCount('mortality_records', 1);
    }

    /** A deceased bird is not even offered - the policy would refuse it anyway. */
    public function test_a_deceased_bird_is_not_offered_in_the_bird_list(): void
    {
        $staff = User::factory()->staff()->create();
        $living = $this->makeBroodcock(['name' => 'Living Bird']);
        $dead = $this->makeBroodcock(['name' => 'Dead Bird', 'status' => BroodcockStatus::Deceased]);

        $component = Livewire::actingAs($staff)->test(Form::class);

        $ids = $component->instance()->eligibleBirds->pluck('id')->all();

        $this->assertContains($living->id, $ids);
        $this->assertNotContains($dead->id, $ids);
        $component->assertSee('Living Bird')->assertDontSee('Dead Bird');
    }

    public function test_the_bird_list_can_be_searched(): void
    {
        $staff = User::factory()->staff()->create();
        $this->makeBroodcock(['name' => 'Kalabaw']);
        $this->makeBroodcock(['name' => 'Tandang']);

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('birdSearch', 'Kalab')
            ->assertSee('Kalabaw')
            ->assertDontSee('Tandang');
    }

    /** Opening the form from a bird's page pre-selects that bird. */
    public function test_the_form_can_be_opened_for_a_specific_bird(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw']);

        Livewire::actingAs($staff)
            ->test(Form::class, ['broodcock' => $bird])
            ->assertSet('broodcock_id', (string) $bird->id);
    }

    // -----------------------------------------------------------------
    // Deleting from the register
    // -----------------------------------------------------------------

    public function test_the_owner_is_asked_to_confirm_by_name_before_deleting(): void
    {
        $owner = User::factory()->owner()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw', 'band_number' => 'GF-7777']);
        $record = MortalityRecord::factory()->forBroodcock($bird)->recordedBy($owner)->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->assertSee('Delete the death record for')
            // The dialog names the bird - never a bare "Are you sure?".
            ->assertSee('Kalabaw (GF-7777)?')
            ->assertSee('will be put back on the');

        // Confirming is not deleting.
        $this->assertNotSoftDeleted('mortality_records', ['id' => $record->id]);
    }

    public function test_deleting_a_record_soft_deletes_it_and_reactivates_the_bird(): void
    {
        $owner = User::factory()->owner()->create();
        $bird = $this->makeBroodcock([
            'name' => 'Kalabaw',
            'band_number' => 'GF-8888',
            'status' => BroodcockStatus::Deceased,
        ]);
        $record = MortalityRecord::factory()->forBroodcock($bird)->recordedBy($owner)->create();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete')
            ->assertSee('The death record for Kalabaw (GF-8888) was removed.')
            ->assertSet('confirmingDeleteId', null);

        $this->assertSoftDeleted('mortality_records', ['id' => $record->id]);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    /**
     * BroodcockFactory belongs to another vertical, so birds are built by hand.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeBroodcock(array $attributes = []): Broodcock
    {
        static $counter = 0;
        $counter++;

        return Broodcock::create(array_merge([
            'band_number' => 'GF-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'name' => 'Bird '.$counter,
            'sex' => Sex::Male,
            'status' => BroodcockStatus::Active,
            'date_hatched' => '2023-01-15',
        ], $attributes));
    }
}
