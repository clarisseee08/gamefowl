<?php

declare(strict_types=1);

namespace Tests\Feature\Health;

use App\Enums\HealthRecordType;
use App\Livewire\Health\BroodcockHealthHistory;
use App\Livewire\Health\Form;
use App\Livewire\Health\Index;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * One test per role per protected action on health records.
 *
 * Health is the one internal module customers are deliberately allowed to see
 * - the thesis promises them "health status" - so the interesting boundary
 * here is not view-vs-not-view but which FIELDS a customer may see.
 */
final class HealthAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Viewing
    // -----------------------------------------------------------------

    public function test_an_owner_can_view_the_health_list(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('health.index'))->assertOk();
    }

    public function test_a_record_keeper_can_view_the_health_list(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('health.index'))->assertOk();
    }

    /**
     * The public are promised visibility of health status - on each bird's own
     * page, not through the farm-wide list, which is a staff screen.
     */
    public function test_the_public_see_a_birds_health_on_its_page(): void
    {
        $bird = Broodcock::factory()->create();
        HealthRecord::factory()->for($bird)->create(['product_name' => 'Newcastle Disease vaccine']);

        Livewire::test(BroodcockHealthHistory::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertSee('Newcastle Disease vaccine');
    }

    public function test_a_guest_cannot_view_health_records(): void
    {
        $this->get(route('health.index'))->assertRedirect(route('login'));
    }

    /**
     * The vaccination schedule is a farm-management screen, not part of the
     * public catalogue.
     */
    public function test_a_guest_cannot_view_the_vaccination_schedule(): void
    {
        $this->get(route('health.schedule'))->assertRedirect(route('login'));
    }

    public function test_staff_can_view_the_vaccination_schedule(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('health.schedule'))->assertOk();
    }

    // -----------------------------------------------------------------
    // Internal remarks must never reach the public
    // -----------------------------------------------------------------

    public function test_a_guest_does_not_see_internal_remarks(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Tala']);
        HealthRecord::factory()->for($bird)->create([
            'record_type' => HealthRecordType::Treatment,
            'product_name' => 'Ivermectin',
            'remarks' => 'INTERNAL-ONLY-REMARK',
        ]);

        Livewire::test(BroodcockHealthHistory::class, ['broodcock' => $bird])
            ->assertOk()
            ->assertSee('Ivermectin')
            ->assertDontSee('INTERNAL-ONLY-REMARK');
    }

    public function test_internal_staff_do_see_remarks(): void
    {
        HealthRecord::factory()->create(['remarks' => 'INTERNAL-ONLY-REMARK']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('health.index'))
            ->assertOk()
            ->assertSee('INTERNAL-ONLY-REMARK');
    }

    // -----------------------------------------------------------------
    // Creating and editing - internal only
    // -----------------------------------------------------------------

    public function test_a_guest_cannot_open_the_health_form(): void
    {
        $this->get(route('health.create'))->assertRedirect(route('login'));
    }

    public function test_a_guest_cannot_record_a_health_record_through_the_component(): void
    {
        Livewire::test(Form::class)
            ->assertForbidden();

        $this->assertDatabaseCount('health_records', 0);
    }

    public function test_a_deactivated_staff_member_cannot_record_a_health_record(): void
    {
        Livewire::actingAs(User::factory()->staff()->inactive()->create())
            ->test(Form::class)
            ->assertForbidden();
    }

    public function test_a_record_keeper_can_edit_a_health_record(): void
    {
        $record = HealthRecord::factory()->create(['product_name' => 'Old Product']);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class, ['record' => $record])
            ->set('product_name', 'New Product')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New Product', $record->fresh()->product_name);
    }

    // -----------------------------------------------------------------
    // Deleting - owner only
    // -----------------------------------------------------------------

    public function test_an_owner_can_delete_a_health_record(): void
    {
        $record = HealthRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete')
            ->assertDispatched('toast')
            ->assertSet('confirmingDeleteId', null);

        $this->assertSoftDeleted('health_records', ['id' => $record->id]);
    }

    /** A record keeper may add and correct health records, but never erase one. */
    public function test_a_record_keeper_cannot_delete_a_health_record(): void
    {
        $record = HealthRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted('health_records', ['id' => $record->id]);
    }

    public function test_a_guest_cannot_delete_a_health_record(): void
    {
        $record = HealthRecord::factory()->create();

        Livewire::test(Index::class)
            ->call('confirmDelete', $record->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted('health_records', ['id' => $record->id]);
    }
}
