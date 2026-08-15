<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Livewire\Performance\BroodcockTimeline;
use App\Livewire\Performance\Form;
use App\Livewire\Performance\Index;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    // -----------------------------------------------------------------
    // Creating
    // -----------------------------------------------------------------

    public function test_staff_can_record_a_performance_event(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create(['name' => 'Kanlaon']);

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->set('weight', '2.10')
            ->set('duration_seconds', '180')
            ->set('rating', '5')
            ->set('remarks', 'Strong finish.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('performance.index'));

        $this->assertDatabaseHas('performance_records', [
            'broodcock_id' => $bird->id,
            'event_type' => PerformanceEventType::Derby->value,
            'result' => PerformanceResult::Win->value,
            'duration_seconds' => 180,
            'rating' => 5,
            'remarks' => 'Strong finish.',
            // Provenance is taken from the session, never from the form.
            'recorded_by' => $staff->id,
        ]);

        $record = PerformanceRecord::query()->firstOrFail();

        $this->assertSame('2026-05-01', $record->event_date->toDateString());
        $this->assertSame('2.10', $record->weight);
    }

    /** `recorded_by` is never accepted from input - it is who is signed in. */
    public function test_the_recorder_cannot_be_impersonated_through_the_form(): void
    {
        $staff = User::factory()->staff()->create();
        $owner = User::factory()->owner()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Sparring->value)
            ->set('result', PerformanceResult::Win->value)
            ->call('save')
            ->assertHasNoErrors();

        $record = PerformanceRecord::query()->firstOrFail();

        $this->assertSame($staff->id, $record->recorded_by);
        $this->assertNotSame($owner->id, $record->recorded_by);
    }

    /** Arriving from a bird's timeline pre-selects that bird. */
    public function test_the_create_form_can_be_opened_with_a_bird_already_chosen(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class, ['broodcock' => $bird])
            ->assertSet('broodcock_id', (string) $bird->id);
    }

    // -----------------------------------------------------------------
    // The non-contest rule
    // -----------------------------------------------------------------

    public function test_a_weigh_in_cannot_be_recorded_as_a_win(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Sparring->value)
            ->set('result', PerformanceResult::Win->value)
            // Switching to a weigh-in AFTER choosing "Win" is the exact way a
            // false outcome would otherwise sneak in.
            ->set('event_type', PerformanceEventType::WeighIn->value)
            ->call('save')
            ->assertHasNoErrors();

        $record = PerformanceRecord::query()->firstOrFail();

        $this->assertSame(PerformanceEventType::WeighIn, $record->event_type);
        $this->assertSame(PerformanceResult::NotApplicable, $record->result);
    }

    public function test_the_result_selector_is_hidden_for_non_contest_events(): void
    {
        $component = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('event_type', PerformanceEventType::Derby->value);

        $this->assertTrue($component->instance()->resultApplies());

        $component->set('event_type', PerformanceEventType::Conditioning->value);

        $this->assertFalse($component->instance()->resultApplies());
        $component->assertSet('result', PerformanceResult::NotApplicable->value);
    }

    public function test_a_conditioning_session_never_reaches_the_win_rate(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->count(9)->conditioning()->for($bird)->create();

        $summary = Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->instance()
            ->summary;

        $this->assertSame(1, $summary->totalContests);
        $this->assertSame(100.0, $summary->winRate);
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    public function test_a_rating_outside_one_to_five_is_rejected(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->set('rating', '9')
            ->call('save')
            ->assertHasErrors(['rating' => 'between']);

        $this->assertDatabaseCount('performance_records', 0);
    }

    public function test_a_negative_weight_is_rejected(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->set('weight', '-1')
            ->call('save')
            ->assertHasErrors(['weight' => 'min']);

        $this->assertDatabaseCount('performance_records', 0);
    }

    public function test_a_future_event_date_is_rejected(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', now()->addWeek()->toDateString())
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->call('save')
            ->assertHasErrors('event_date');

        $this->assertDatabaseCount('performance_records', 0);
    }

    public function test_a_contest_must_state_its_result(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->call('save')
            ->assertHasErrors('result');
    }

    public function test_validation_messages_are_written_in_plain_language(): void
    {
        $bird = Broodcock::factory()->create();

        $errors = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('rating', '9')
            ->call('save')
            ->errors();

        $this->assertSame(
            'Please give a rating from 1 to 5 stars, or leave it blank.',
            $errors->first('rating'),
        );
        $this->assertSame('Please choose the result.', $errors->first('result'));
    }

    // -----------------------------------------------------------------
    // Editing and deleting
    // -----------------------------------------------------------------

    public function test_staff_can_edit_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->loss()->rating(2)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class, ['record' => $record])
            ->assertSet('result', PerformanceResult::Loss->value)
            ->assertSet('rating', '2')
            ->set('result', PerformanceResult::Win->value)
            ->set('rating', '5')
            ->call('save')
            ->assertHasNoErrors();

        $record->refresh();

        $this->assertSame(PerformanceResult::Win, $record->result);
        $this->assertSame(5, $record->rating);
    }

    /** Editing must not silently reassign who recorded the event. */
    public function test_editing_keeps_the_original_recorder(): void
    {
        $original = User::factory()->staff()->create();
        $record = PerformanceRecord::factory()->create(['recorded_by' => $original->id]);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class, ['record' => $record])
            ->set('rating', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($original->id, $record->refresh()->recorded_by);
    }

    public function test_the_owner_can_soft_delete_a_record_after_confirming(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Kanlaon']);
        $record = PerformanceRecord::factory()->derby()->for($bird)->on('2026-05-01')->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->assertSet('confirmingDeleteId', $record->id)
            // The dialog must name the record, not just say "are you sure?".
            ->assertSee('Kanlaon')
            ->assertSee('Derby')
            ->assertSee('01 May 2026');

        $component->call('delete')
            ->assertSet('confirmingDeleteId', null)
            ->assertSee('has been removed');

        $this->assertSoftDeleted($record);
    }

    public function test_cancelling_the_dialog_leaves_the_record_alone(): void
    {
        $record = PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('cancelDelete')
            ->assertSet('confirmingDeleteId', null);

        $this->assertNotSoftDeleted($record);
    }

    // -----------------------------------------------------------------
    // The per-bird timeline
    // -----------------------------------------------------------------

    public function test_the_timeline_shows_a_birds_events_newest_first(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->for($bird)->on('2026-01-01')->create();
        PerformanceRecord::factory()->for($bird)->on('2026-06-01')->create();
        PerformanceRecord::factory()->for($bird)->on('2026-03-01')->create();

        $events = Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertOk()
            ->instance()
            ->events;

        $this->assertSame(
            ['2026-06-01', '2026-03-01', '2026-01-01'],
            $events->pluck('event_date')->map(fn ($date) => $date->toDateString())->all(),
        );
    }

    public function test_the_timeline_shows_only_that_birds_events(): void
    {
        $bird = Broodcock::factory()->create();
        $other = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(2)->for($bird)->create();
        PerformanceRecord::factory()->count(5)->for($other)->create();

        $component = Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird]);

        $this->assertSame(2, $component->instance()->events->total());
        $this->assertSame(2, $component->instance()->summary->totalEvents);
    }

    public function test_the_timeline_shows_the_summary_above_the_events(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(3)->win()->for($bird)->create();
        PerformanceRecord::factory()->loss()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertSee('Win Rate')
            ->assertSee('75.0%')
            ->assertSee('3-1-0');
    }

    public function test_a_bird_with_no_events_gets_an_empty_state_that_says_what_to_do(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertSee('No performance recorded yet')
            ->assertSee('Add the first record')
            ->assertSee('No contests yet');
    }

    public function test_deleting_from_the_timeline_updates_the_summary_immediately(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->for($bird)->create();
        $loss = PerformanceRecord::factory()->loss()->for($bird)->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird]);

        $this->assertSame(50.0, $component->instance()->summary->winRate);

        $component->call('confirmDelete', $loss->id)->call('delete');

        $this->assertSoftDeleted($loss);
        $this->assertSame(100.0, $component->instance()->summary->winRate);
    }

    /** A crafted id must not reach another bird's record through this component. */
    public function test_the_timeline_cannot_delete_another_birds_record(): void
    {
        $bird = Broodcock::factory()->create();
        $other = Broodcock::factory()->create();
        $foreign = PerformanceRecord::factory()->for($other)->create();

        $component = Livewire::actingAs(User::factory()->owner()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird]);

        // The lookup is scoped to this bird, so a foreign id simply is not found.
        $this->assertThrows(
            fn () => $component->call('confirmDelete', $foreign->id),
            ModelNotFoundException::class,
        );

        $this->assertNotSoftDeleted($foreign);
    }

    // -----------------------------------------------------------------
    // Performance efficiency - no N+1
    // -----------------------------------------------------------------

    /**
     * Model::shouldBeStrict() already makes a lazy load throw, so rendering at
     * all proves the relations are eager-loaded. This adds the stronger claim:
     * the query count does not grow with the number of rows.
     */
    public function test_the_list_issues_the_same_number_of_queries_regardless_of_row_count(): void
    {
        $user = User::factory()->staff()->create();

        Broodcock::factory()->count(2)->create()->each(
            fn (Broodcock $bird) => PerformanceRecord::factory()->count(2)->for($bird)->create(),
        );

        $small = $this->countQueriesWhileRendering(fn () => Livewire::actingAs($user)->test(Index::class));

        Broodcock::factory()->count(5)->create()->each(
            fn (Broodcock $bird) => PerformanceRecord::factory()->count(2)->for($bird)->create(),
        );

        $large = $this->countQueriesWhileRendering(fn () => Livewire::actingAs($user)->test(Index::class));

        $this->assertSame($small, $large, "The list ran {$large} queries for 14 rows but {$small} for 4 - that is an N+1.");
    }

    public function test_the_timeline_issues_the_same_number_of_queries_regardless_of_row_count(): void
    {
        $user = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(2)->for($bird)->create();
        $small = $this->countQueriesWhileRendering(
            fn () => Livewire::actingAs($user)->test(BroodcockTimeline::class, ['broodcock' => $bird]),
        );

        PerformanceRecord::factory()->count(10)->for($bird)->create();
        $large = $this->countQueriesWhileRendering(
            fn () => Livewire::actingAs($user)->test(BroodcockTimeline::class, ['broodcock' => $bird]),
        );

        $this->assertSame($small, $large, "The timeline ran {$large} queries for 12 events but {$small} for 2 - that is an N+1.");
    }

    private function countQueriesWhileRendering(callable $render): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $render();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
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
