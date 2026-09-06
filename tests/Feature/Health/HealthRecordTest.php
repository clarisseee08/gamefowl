<?php

declare(strict_types=1);

namespace Tests\Feature\Health;

use App\Enums\HealthRecordType;
use App\Livewire\Health\BroodcockHealthHistory;
use App\Livewire\Health\Form;
use App\Livewire\Health\Index;
use App\Livewire\Health\Schedule;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Health records and the vaccination schedule.
 *
 * The schedule is the part that turns stored dates into something actionable,
 * so overdue/due-soon classification is covered at the boundaries rather than
 * just in the happy middle.
 */
final class HealthRecordTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Recording
    // -----------------------------------------------------------------

    public function test_staff_can_record_a_health_record(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('record_type', HealthRecordType::Vaccination->value)
            ->set('product_name', 'Newcastle B1')
            ->set('dosage', '0.5 ml')
            ->set('checkup_date', today()->toDateString())
            ->set('next_due_date', today()->addDays(30)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('health_records', [
            'broodcock_id' => $bird->id,
            'record_type' => HealthRecordType::Vaccination->value,
            'product_name' => 'Newcastle B1',
        ]);
    }

    /** The person recording is taken from the session, never from the form. */
    public function test_the_recorder_is_taken_from_the_signed_in_user(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('record_type', HealthRecordType::Checkup->value)
            ->set('checkup_date', today()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($staff->id, HealthRecord::first()->recorded_by);
    }

    public function test_a_follow_up_date_cannot_be_before_the_checkup_date(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('record_type', HealthRecordType::Vaccination->value)
            ->set('checkup_date', today()->toDateString())
            ->set('next_due_date', today()->subDays(5)->toDateString())
            ->call('save')
            ->assertHasErrors('next_due_date');

        $this->assertDatabaseCount('health_records', 0);
    }

    public function test_a_health_record_requires_a_bird_a_type_and_a_date(): void
    {
        $staff = User::factory()->staff()->create();

        Livewire::actingAs($staff)
            ->test(Form::class)
            ->set('broodcock_id', '')
            ->set('record_type', '')
            ->set('checkup_date', '')
            ->call('save')
            ->assertHasErrors(['broodcock_id', 'record_type', 'checkup_date']);
    }

    // -----------------------------------------------------------------
    // Schedule classification - the boundaries are what matter
    // -----------------------------------------------------------------

    public function test_a_record_due_yesterday_is_overdue(): void
    {
        $record = HealthRecord::factory()->create([
            'checkup_date' => today()->subDays(40),
            'next_due_date' => today()->subDay(),
        ]);

        $this->assertTrue($record->isOverdue());
        $this->assertFalse($record->isDueSoon());
        $this->assertSame('Overdue', $record->scheduleState());
    }

    public function test_a_record_due_today_is_not_yet_overdue(): void
    {
        $record = HealthRecord::factory()->create([
            'checkup_date' => today()->subDays(30),
            'next_due_date' => today(),
        ]);

        // Due today means due today - not late.
        $this->assertFalse($record->isOverdue());
        $this->assertTrue($record->isDueSoon());
    }

    public function test_a_record_with_no_follow_up_is_neither_overdue_nor_due_soon(): void
    {
        $record = HealthRecord::factory()->create([
            'record_type' => HealthRecordType::Checkup,
            'checkup_date' => today()->subDays(90),
            'next_due_date' => null,
        ]);

        $this->assertFalse($record->isOverdue());
        $this->assertFalse($record->isDueSoon());
        $this->assertSame('No follow-up needed', $record->scheduleState());
    }

    public function test_a_record_beyond_the_warning_window_is_merely_scheduled(): void
    {
        $beyond = (int) config('gfms.vaccination_warning_days') + 10;

        $record = HealthRecord::factory()->create([
            'checkup_date' => today(),
            'next_due_date' => today()->addDays($beyond),
        ]);

        $this->assertFalse($record->isOverdue());
        $this->assertFalse($record->isDueSoon());
        $this->assertSame('Scheduled', $record->scheduleState());
    }

    public function test_the_schedule_screen_separates_overdue_from_due_soon(): void
    {
        $staff = User::factory()->staff()->create();

        $lateBird = Broodcock::factory()->create(['name' => 'LateBird']);
        $soonBird = Broodcock::factory()->create(['name' => 'SoonBird']);

        HealthRecord::factory()->for($lateBird)->create([
            'checkup_date' => today()->subDays(60),
            'next_due_date' => today()->subDays(14),
        ]);
        HealthRecord::factory()->for($soonBird)->create([
            'checkup_date' => today()->subDays(10),
            'next_due_date' => today()->addDays(5),
        ]);

        Livewire::actingAs($staff)
            ->test(Schedule::class)
            ->assertOk()
            ->assertSee('LateBird')
            ->assertSee('SoonBird');
    }

    public function test_the_overdue_and_due_soon_scopes_do_not_overlap(): void
    {
        HealthRecord::factory()->create(['checkup_date' => today()->subDays(60), 'next_due_date' => today()->subDays(3)]);
        HealthRecord::factory()->create(['checkup_date' => today()->subDays(10), 'next_due_date' => today()->addDays(3)]);
        HealthRecord::factory()->create(['checkup_date' => today(), 'next_due_date' => today()->addYear()]);

        $overdue = HealthRecord::query()->overdue()->pluck('id');
        $dueSoon = HealthRecord::query()->dueSoon()->pluck('id');

        $this->assertCount(1, $overdue);
        $this->assertCount(1, $dueSoon);
        $this->assertEmpty(
            $overdue->intersect($dueSoon),
            'A record cannot be both overdue and due soon - the two lists must be disjoint.'
        );
    }

    // -----------------------------------------------------------------
    // Listing
    // -----------------------------------------------------------------

    public function test_the_list_does_not_run_a_query_per_row(): void
    {
        $staff = User::factory()->staff()->create();

        HealthRecord::factory()->count(3)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($staff)->test(Index::class)->html();
        $withThree = count(DB::getQueryLog());
        DB::disableQueryLog();

        HealthRecord::factory()->count(12)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($staff)->test(Index::class)->html();
        $withFifteen = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(
            $withThree,
            $withFifteen,
            "Listing 3 records took {$withThree} queries but 15 took {$withFifteen}; the bird relation is not eager-loaded."
        );
    }

    public function test_the_list_can_be_filtered_by_record_type(): void
    {
        $staff = User::factory()->staff()->create();

        // Assert on the PRODUCT NAME, not the bird name. Every bird appears in
        // the "filter by bird" dropdown regardless of the type filter, so
        // assertDontSee() on a bird name would fail against the <select>
        // options even when the table itself is filtered correctly.
        HealthRecord::factory()->vaccination()->create(['product_name' => 'ZarnakVaxSerum']);
        HealthRecord::factory()->deworming()->create(['product_name' => 'QuillanWormPaste']);

        Livewire::actingAs($staff)
            ->test(Index::class)
            // NB: 'type' is only the URL alias; the property is $recordType.
            ->set('recordType', HealthRecordType::Vaccination->value)
            ->assertSee('ZarnakVaxSerum')
            ->assertDontSee('QuillanWormPaste');
    }

    /** The underlying scope, independent of how the screen renders it. */
    public function test_the_record_type_scope_filters_at_the_query_level(): void
    {
        HealthRecord::factory()->vaccination()->count(2)->create();
        HealthRecord::factory()->deworming()->count(3)->create();

        $this->assertCount(2, HealthRecord::query()->ofType(HealthRecordType::Vaccination)->get());
        $this->assertCount(3, HealthRecord::query()->ofType(HealthRecordType::Deworming)->get());
        // A null/empty filter must return everything, not nothing.
        $this->assertCount(5, HealthRecord::query()->ofType(null)->get());
    }

    public function test_the_list_shows_an_empty_state_that_says_what_to_do_next(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertOk()
            ->assertSee('No health records', escape: false);
    }

    // -----------------------------------------------------------------
    // The health panel on a bird page
    // -----------------------------------------------------------------

    /**
     * The panel could add a record but not correct one, while the farm-wide
     * Health list could do both - so a keeper who spotted a typo on the bird
     * they were looking at had to go and find the row somewhere else.
     */
    public function test_the_health_panel_on_a_bird_offers_edit_on_each_row(): void
    {
        $bird = Broodcock::factory()->create();
        $record = HealthRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockHealthHistory::class, ['broodcock' => $bird])
            ->assertSee('Edit')
            ->assertSee(route('health.edit', $record), escape: false);
    }

    public function test_a_customer_sees_no_edit_link_on_the_health_panel(): void
    {
        $bird = Broodcock::factory()->create();
        $record = HealthRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(BroodcockHealthHistory::class, ['broodcock' => $bird])
            ->assertDontSee(route('health.edit', $record), escape: false);
    }
}
