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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * One test per role per protected action, asserting both the outcome and that
 * the data did not change. Hiding a button is a courtesy; the Policy is the
 * gate, and this file is the evidence that the gate is shut.
 */
final class PerformanceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoutesIfMissing();
    }

    // -----------------------------------------------------------------
    // Viewing - customers are promised performance history
    // -----------------------------------------------------------------

    public function test_an_owner_can_view_the_performance_list(): void
    {
        PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->assertOk();
    }

    public function test_staff_can_view_the_performance_list(): void
    {
        PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertOk();
    }

    public function test_a_customer_can_view_the_performance_list(): void
    {
        PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->assertOk();
    }

    public function test_a_deactivated_user_cannot_view_the_performance_list(): void
    {
        PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->inactive()->create())
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_a_customer_can_view_a_birds_timeline(): void
    {
        $bird = Broodcock::factory()->create();
        PerformanceRecord::factory()->win()->for($bird)->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertSee('Win Rate');
    }

    // -----------------------------------------------------------------
    // Internal remarks must never reach a customer
    // -----------------------------------------------------------------

    public function test_a_customer_never_sees_internal_remarks_on_the_timeline(): void
    {
        $bird = Broodcock::factory()->create();
        PerformanceRecord::factory()->win()->for($bird)->create([
            'remarks' => 'Secret internal note about conditioning cost.',
        ]);

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertDontSee('Secret internal note')
            ->assertDontSee('Remarks (staff only)');
    }

    public function test_staff_do_see_internal_remarks_on_the_timeline(): void
    {
        $bird = Broodcock::factory()->create();
        PerformanceRecord::factory()->win()->for($bird)->create([
            'remarks' => 'Secret internal note about conditioning cost.',
        ]);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->assertSee('Secret internal note about conditioning cost.');
    }

    public function test_the_remarks_policy_agrees_with_what_the_view_renders(): void
    {
        $record = PerformanceRecord::factory()->create(['remarks' => 'Internal.']);

        $this->assertTrue(User::factory()->owner()->create()->can('viewRemarks', $record));
        $this->assertTrue(User::factory()->staff()->create()->can('viewRemarks', $record));
        $this->assertFalse(User::factory()->customer()->create()->can('viewRemarks', $record));
    }

    // -----------------------------------------------------------------
    // Creating - internal staff only
    // -----------------------------------------------------------------

    public function test_a_customer_cannot_open_the_create_form(): void
    {
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Form::class)
            ->assertForbidden();

        $this->assertDatabaseCount('performance_records', 0);
    }

    public function test_a_deactivated_staff_member_cannot_open_the_create_form(): void
    {
        Livewire::actingAs(User::factory()->staff()->inactive()->create())
            ->test(Form::class)
            ->assertForbidden();

        $this->assertDatabaseCount('performance_records', 0);
    }

    public function test_an_owner_can_create_a_performance_record(): void
    {
        $owner = User::factory()->owner()->create();
        $bird = Broodcock::factory()->create();

        Livewire::actingAs($owner)
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('performance_records', 1);
    }

    // -----------------------------------------------------------------
    // Updating - internal staff only
    // -----------------------------------------------------------------

    public function test_a_customer_cannot_open_the_edit_form(): void
    {
        $record = PerformanceRecord::factory()->loss()->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Form::class, ['record' => $record])
            ->assertForbidden();

        $this->assertSame(PerformanceResult::Loss, $record->refresh()->result);
    }

    public function test_staff_can_update_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->loss()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class, ['record' => $record])
            ->set('result', PerformanceResult::Win->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(PerformanceResult::Win, $record->refresh()->result);
    }

    // -----------------------------------------------------------------
    // Deleting - owner only
    // -----------------------------------------------------------------

    public function test_staff_cannot_delete_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($record);
    }

    /** Skipping the dialog and calling delete() directly must fail too. */
    public function test_staff_cannot_delete_by_calling_delete_directly(): void
    {
        $record = PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->set('confirmingDeleteId', $record->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($record);
    }

    public function test_a_customer_cannot_delete_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->customer()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($record);
    }

    public function test_the_owner_can_delete_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete');

        $this->assertSoftDeleted($record);
    }

    public function test_staff_cannot_delete_from_the_timeline_either(): void
    {
        $bird = Broodcock::factory()->create();
        $record = PerformanceRecord::factory()->for($bird)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BroodcockTimeline::class, ['broodcock' => $bird])
            ->call('confirmDelete', $record->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($record);
    }

    /** Nothing in this module hard-deletes, whatever the role. */
    public function test_nobody_may_force_delete_a_performance_record(): void
    {
        $record = PerformanceRecord::factory()->create();

        $this->assertFalse(User::factory()->owner()->create()->can('forceDelete', $record));
        $this->assertFalse(User::factory()->staff()->create()->can('forceDelete', $record));
        $this->assertFalse(User::factory()->customer()->create()->can('forceDelete', $record));
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
