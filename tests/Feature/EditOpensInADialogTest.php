<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Breeding\Show as BreedingShow;
use App\Livewire\Broodcocks\Show as BirdShow;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Edit never leaves the page it starts on (the owner's decision): on a bird's
 * page and on a mating's page it opens the existing form in a dialog, and
 * only for someone allowed to edit.
 */
final class EditOpensInADialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_bird_opens_its_form_in_a_dialog_on_the_bird_page(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Kingpin']);

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BirdShow::class, ['broodcock' => $bird])
            ->assertSee('aria-labelledby="broodcocks-form-title"', escape: false)
            // The bird's form is built with the page, so Edit opens at once.
            ->assertSeeLivewire('broodcocks.form')
            ->call('openForm')
            ->assertSet('showForm', true)
            ->assertSee('Edit Kingpin');
    }

    public function test_editing_a_mating_opens_its_form_in_a_dialog_on_the_mating_page(): void
    {
        $record = BreedingRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(BreedingShow::class, ['record' => $record])
            ->assertSee('aria-labelledby="breeding-form-title"', escape: false)
            ->call('openForm')
            ->assertSet('showForm', true)
            ->assertSee('Edit Breeding Record');
    }

    /** A bird's page is public, so a guest is on the very screen that holds the dialog. */
    public function test_a_guest_gets_no_edit_dialog_and_cannot_open_one(): void
    {
        $bird = Broodcock::factory()->create();

        Livewire::test(BirdShow::class, ['broodcock' => $bird])
            ->assertDontSee('broodcocks-form-title', escape: false)
            ->call('openForm')
            ->assertForbidden();
    }
}
