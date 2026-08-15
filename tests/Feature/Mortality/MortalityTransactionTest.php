<?php

declare(strict_types=1);

namespace Tests\Feature\Mortality;

use App\Actions\Mortality\DeleteMortality;
use App\Actions\Mortality\RecordMortality;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * The single most important correctness requirement in the system.
 *
 * Recording a death writes two tables: it inserts a mortality row AND sets the
 * bird's status to `deceased`. Half of that is worse than none of it:
 *
 *  - a mortality row without the status change leaves a dead bird in the live
 *    flock, still offered as a breeding parent; and
 *  - the status change without the row is a death with no cause, no date and
 *    no audit trail - and because `mortality_records.broodcock_id` is UNIQUE,
 *    the missing row can never be added afterwards.
 *
 * A happy-path test proves nothing about this: both writes succeed there
 * whether or not a transaction exists. These tests instead FORCE the second
 * write to fail, confirm the first write really had happened at that moment,
 * and then assert that it is gone.
 */
final class MortalityTransactionTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Recording
    // -----------------------------------------------------------------

    /**
     * The proof.
     *
     * A `Broodcock::updating` listener throws exactly where the status update
     * happens - i.e. AFTER the mortality row has been inserted. Before it
     * throws it records whether the mortality row is visible from inside the
     * transaction, so the test can distinguish "the insert was rolled back"
     * from "the insert never ran at all". Only the first proves a transaction.
     */
    public function test_a_failed_status_update_rolls_the_mortality_row_back(): void
    {
        $actor = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['name' => 'Kalabaw', 'status' => BroodcockStatus::Breeding]);

        $rowWasVisibleInsideTheTransaction = false;

        Broodcock::updating(function () use (&$rowWasVisibleInsideTheTransaction, $bird): void {
            // We are inside RecordMortality's DB::transaction() and the INSERT
            // has already run. Read it back on the same connection.
            $rowWasVisibleInsideTheTransaction = DB::table('mortality_records')
                ->where('broodcock_id', $bird->id)
                ->exists();

            throw new RuntimeException('Simulated failure while updating the bird status.');
        });

        try {
            app(RecordMortality::class)->handle($bird, [
                'date_of_death' => '2026-08-01',
                'cause_of_death' => 'Disease',
                'disposal_method' => 'Buried',
                'remarks' => null,
            ], $actor);

            $this->fail('RecordMortality should have propagated the failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated failure while updating the bird status.', $e->getMessage());
        }

        // 1. The mortality row genuinely existed mid-transaction. Without this
        //    assertion the test below would also pass on code that simply
        //    never got as far as inserting.
        $this->assertTrue(
            $rowWasVisibleInsideTheTransaction,
            'The mortality row should have been inserted before the status update failed.'
        );

        // 2. ...and it is gone. Rolled back with the failed status update.
        $this->assertDatabaseCount('mortality_records', 0);
        $this->assertDatabaseMissing('mortality_records', ['broodcock_id' => $bird->id]);

        // 3. The bird is untouched - still Breeding, not Deceased.
        $this->assertSame(BroodcockStatus::Breeding, $bird->fresh()->status);
        $this->assertDatabaseHas('broodcocks', [
            'id' => $bird->id,
            'status' => BroodcockStatus::Breeding->value,
        ]);
    }

    /**
     * The other direction: if the INSERT fails, the bird must not be left
     * marked deceased.
     */
    public function test_a_failed_mortality_insert_leaves_the_bird_status_alone(): void
    {
        $actor = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Active]);

        MortalityRecord::creating(function (): void {
            throw new RuntimeException('Simulated failure while inserting the mortality row.');
        });

        try {
            app(RecordMortality::class)->handle($bird, [
                'date_of_death' => '2026-08-01',
                'cause_of_death' => 'Injury',
                'disposal_method' => null,
                'remarks' => null,
            ], $actor);

            $this->fail('RecordMortality should have propagated the failure.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('mortality_records', 0);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    /** The happy path, for contrast: both writes land, and they land together. */
    public function test_a_successful_recording_writes_both_tables(): void
    {
        $actor = User::factory()->staff()->create();
        $bird = $this->makeBroodcock(['status' => BroodcockStatus::Active]);

        $record = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => '2026-08-01',
            'cause_of_death' => 'Old age',
            'disposal_method' => 'Buried',
            'remarks' => 'Passed away in the night.',
        ], $actor);

        $this->assertDatabaseHas('mortality_records', [
            'id' => $record->id,
            'broodcock_id' => $bird->id,
            'cause_of_death' => 'Old age',
            'disposal_method' => 'Buried',
            // recorded_by comes from the actor, never from the caller's data.
            'recorded_by' => $actor->id,
        ]);

        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
    }

    /** recorded_by is the signed-in user even if the data says otherwise. */
    public function test_recorded_by_cannot_be_spoofed_through_the_data_array(): void
    {
        $actor = User::factory()->staff()->create();
        $someoneElse = User::factory()->owner()->create();
        $bird = $this->makeBroodcock();

        $record = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => '2026-08-01',
            'cause_of_death' => 'Disease',
            'recorded_by' => $someoneElse->id,
        ], $actor);

        $this->assertSame($actor->id, $record->recorded_by);
    }

    // -----------------------------------------------------------------
    // Deleting
    // -----------------------------------------------------------------

    /**
     * Deleting is a two-table write too - soft-delete the row AND put the bird
     * back on the active list - so it gets the same treatment.
     */
    public function test_a_failed_status_restore_rolls_the_soft_delete_back(): void
    {
        $actor = User::factory()->owner()->create();
        $bird = $this->makeBroodcock();
        $record = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => '2026-08-01',
            'cause_of_death' => 'Disease',
        ], $actor);

        $softDeleteWasVisibleInsideTheTransaction = false;

        Broodcock::updating(function () use (&$softDeleteWasVisibleInsideTheTransaction, $record): void {
            $softDeleteWasVisibleInsideTheTransaction = DB::table('mortality_records')
                ->where('id', $record->id)
                ->whereNotNull('deleted_at')
                ->exists();

            throw new RuntimeException('Simulated failure while restoring the bird status.');
        });

        try {
            app(DeleteMortality::class)->handle($record);
            $this->fail('DeleteMortality should have propagated the failure.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertTrue(
            $softDeleteWasVisibleInsideTheTransaction,
            'The record should have been soft-deleted before the status restore failed.'
        );

        // Both undone: the record is back, and the bird is still Deceased.
        $this->assertNotSoftDeleted('mortality_records', ['id' => $record->id]);
        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
    }

    public function test_a_successful_delete_soft_deletes_and_reactivates_the_bird(): void
    {
        $actor = User::factory()->owner()->create();
        $bird = $this->makeBroodcock();
        $record = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => '2026-08-01',
            'cause_of_death' => 'Disease',
        ], $actor);

        app(DeleteMortality::class)->handle($record);

        // Soft delete - nothing is ever hard-deleted in an audit-trail system.
        $this->assertSoftDeleted('mortality_records', ['id' => $record->id]);
        $this->assertSame(BroodcockStatus::Active, $bird->fresh()->status);
    }

    /**
     * The UNIQUE index on broodcock_id counts soft-deleted rows, so a death
     * that was recorded and then deleted must still be recordable again.
     */
    public function test_a_death_can_be_recorded_again_after_the_record_was_deleted(): void
    {
        $actor = User::factory()->owner()->create();
        $bird = $this->makeBroodcock();

        $first = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => '2026-08-01',
            'cause_of_death' => 'Disease',
        ], $actor);

        app(DeleteMortality::class)->handle($first);

        $second = app(RecordMortality::class)->handle($bird->fresh(), [
            'date_of_death' => '2026-08-02',
            'cause_of_death' => 'Injury',
        ], $actor);

        $this->assertNotSoftDeleted('mortality_records', ['id' => $second->id]);
        $this->assertSame('Injury', $second->cause_of_death);
        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
        $this->assertDatabaseCount('mortality_records', 1);
    }

    /**
     * BroodcockFactory belongs to another vertical, so birds are built by hand
     * here rather than depending on a file this module does not own.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeBroodcock(array $attributes = []): Broodcock
    {
        static $counter = 0;
        $counter++;

        return Broodcock::create(array_merge([
            'band_number' => 'TX-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'name' => 'Test Bird '.$counter,
            'sex' => Sex::Male,
            'status' => BroodcockStatus::Active,
            'date_hatched' => '2023-01-15',
        ], $attributes));
    }
}
