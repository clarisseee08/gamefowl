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
 * Deleting several birds from the list asks first, in a designed dialog that
 * names the birds - not the browser's confirm(), which could do neither.
 * Opening or cancelling the dialog must never delete anything.
 */
final class BulkDeleteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dialog_names_the_selected_birds_and_cancelling_keeps_them(): void
    {
        $first = Broodcock::factory()->create(['band_number' => '0113']);
        $second = Broodcock::factory()->create(['band_number' => '0114']);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->set('selected', [$first->id, $second->id])
            ->assertDontSee('Delete 2 birds?', false)
            ->call('confirmDeleteSelected')
            ->assertSet('confirmingBulkDelete', true)
            ->assertSee('0113, 0114')
            ->assertSee('Yes, delete these birds')
            ->call('cancelDeleteSelected')
            ->assertSet('confirmingBulkDelete', false)
            ->assertDontSee('Yes, delete these birds');

        $this->assertNotSoftDeleted($first);
        $this->assertNotSoftDeleted($second);
    }

    public function test_confirming_deletes_the_selection_and_says_so(): void
    {
        $birds = Broodcock::factory()->count(2)->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->set('selected', $birds->pluck('id')->all())
            ->call('confirmDeleteSelected')
            ->call('deleteSelected')
            ->assertSet('confirmingBulkDelete', false)
            ->assertSet('selected', [])
            ->assertDispatched('toast', message: '2 birds removed from the active records.', tone: 'ok');

        foreach ($birds as $bird) {
            $this->assertSoftDeleted($bird);
        }
    }

    public function test_the_dialog_does_not_open_with_nothing_selected(): void
    {
        Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->call('confirmDeleteSelected')
            ->assertSet('confirmingBulkDelete', false);
    }

    public function test_clearing_the_selection_closes_the_dialog(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Index::class)
            ->set('selected', [$bird->id])
            ->call('confirmDeleteSelected')
            ->call('clearSelection')
            ->assertSet('confirmingBulkDelete', false);

        $this->assertNotSoftDeleted($bird);
    }

    public function test_the_list_opens_with_the_count_of_birds_on_record(): void
    {
        Broodcock::factory()->count(3)->create();
        Broodcock::factory()->external()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertSee('birds on record')
            ->assertDontSee('birds on the farm')
            ->assertSeeHtml('>3</span>');
    }

    /**
     * Each row carries its actions as icons in a visible Actions column (the
     * owner's choice): view and edit for staff, and delete for the owner,
     * which opens the same confirmation as a bulk delete, for that one bird.
     */
    public function test_each_row_offers_view_edit_and_for_the_owner_delete(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Kingpin']);

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('broodcocks.index'))
            ->assertOk()
            ->assertSee('Actions')
            ->assertSee('aria-label="View Kingpin"', escape: false)
            ->assertSee('aria-label="Edit Kingpin"', escape: false)
            ->assertSee('aria-label="Delete Kingpin"', escape: false)
            ->assertSee('confirmDeleteSelected()', escape: false);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('broodcocks.index'))
            ->assertSee('aria-label="Edit Kingpin"', escape: false)
            ->assertDontSee('aria-label="Delete Kingpin"', escape: false);
    }
}
