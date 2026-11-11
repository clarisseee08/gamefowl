<?php

declare(strict_types=1);

namespace Tests\Feature\Archive;

use App\Actions\Mortality\RecordMortality;
use App\Actions\Photos\ArchivePhoto;
use App\Enums\BroodcockStatus;
use App\Livewire\Archive\Index;
use App\Livewire\Broodcocks\Form;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Archive: everything that was archived, kept for good, and restorable by
 * the owner. Nothing in the system is ever erased.
 */
final class ArchivePageTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->owner()->create(['full_name' => 'Salvador Guadalupe']);
    }

    // -----------------------------------------------------------------
    // Who can reach it
    // -----------------------------------------------------------------

    public function test_the_owner_can_open_the_archive(): void
    {
        $this->actingAs($this->owner())
            ->get(route('archive.index'))
            ->assertOk()
            ->assertSee('Archive')
            ->assertSee('Nothing here is ever erased');
    }

    public function test_a_record_keeper_cannot_open_the_archive(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('archive.index'))
            ->assertForbidden();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('archive.index'))->assertRedirect(route('login'));
    }

    public function test_only_the_owner_is_offered_the_archive_in_the_sidebar(): void
    {
        $this->actingAs($this->owner())->get(route('dashboard'))
            ->assertSee(route('archive.index'), escape: false);

        $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))
            ->assertDontSee(route('archive.index'), escape: false);
    }

    // -----------------------------------------------------------------
    // What it lists
    // -----------------------------------------------------------------

    public function test_the_gamefowl_tab_lists_archived_birds_with_who_archived_them(): void
    {
        $owner = $this->owner();
        $archived = Broodcock::factory()->create(['name' => 'Reyna', 'band_number' => 'SW-1002']);
        Broodcock::factory()->create(['name' => 'StillHere']);

        $this->actingAs($owner);
        $archived->delete();

        Livewire::test(Index::class)
            ->assertSee('Reyna')
            ->assertSee('SW-1002')
            ->assertSee('Salvador Guadalupe')
            ->assertDontSee('StillHere');
    }

    public function test_each_tab_counts_and_lists_its_own_archived_records(): void
    {
        $this->actingAs($this->owner());
        $bird = Broodcock::factory()->create(['name' => 'Agila']);

        HealthRecord::factory()->for($bird)->create(['product_name' => 'Ivermectin'])->delete();
        PerformanceRecord::factory()->for($bird)->sparring()->create()->delete();
        BreedingRecord::factory()->create()->delete();

        Livewire::test(Index::class)
            ->assertSee('Health')
            ->set('tab', 'health')
            ->assertSee('Ivermectin')
            ->assertSee('Agila')
            ->set('tab', 'performance')
            ->assertSee('Sparring')
            ->set('tab', 'breeding')
            ->assertSee('Restore');
    }

    public function test_searching_narrows_the_tab(): void
    {
        $this->actingAs($this->owner());
        Broodcock::factory()->create(['name' => 'Reyna'])->delete();
        Broodcock::factory()->create(['name' => 'Bagwis'])->delete();

        Livewire::test(Index::class)
            ->set('search', 'Rey')
            ->assertSee('Reyna')
            ->assertDontSee('Bagwis');
    }

    // -----------------------------------------------------------------
    // Restoring
    // -----------------------------------------------------------------

    public function test_restoring_a_bird_asks_first_then_brings_it_back(): void
    {
        $this->actingAs($this->owner());
        $bird = Broodcock::factory()->create(['name' => 'Reyna']);
        $bird->delete();

        Livewire::test(Index::class)
            ->call('confirmRestore', 'gamefowl', $bird->id)
            ->assertSee('Restore Reyna?')
            ->call('cancelRestore')
            ->assertSet('restoringId', null);

        $this->assertSoftDeleted($bird);

        Livewire::test(Index::class)
            ->call('confirmRestore', 'gamefowl', $bird->id)
            ->call('restore')
            ->assertSet('restoringId', null)
            ->assertDispatched('toast', message: 'Reyna has been restored.', tone: 'ok')
            ->assertDontSee('Reyna has been restored.');

        $this->assertNotSoftDeleted($bird);
    }

    public function test_restoring_a_death_record_marks_the_bird_deceased_again(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);
        $bird = Broodcock::factory()->create(['status' => BroodcockStatus::Active]);
        $record = app(RecordMortality::class)->handle($bird, [
            'date_of_death' => today()->toDateString(), 'cause_of_death' => 'Disease',
        ], $owner);

        // Archiving the death record puts the bird back on the active list.
        $record->delete();
        $bird->update(['status' => BroodcockStatus::Active]);

        Livewire::test(Index::class)
            ->set('tab', 'mortality')
            ->call('confirmRestore', 'mortality', $record->id)
            ->call('restore');

        $this->assertNotSoftDeleted($record);
        $this->assertSame(BroodcockStatus::Deceased, $bird->fresh()->status);
    }

    public function test_restoring_records_of_every_other_kind(): void
    {
        $this->actingAs($this->owner());
        $records = [
            'health' => HealthRecord::factory()->create(),
            'performance' => PerformanceRecord::factory()->create(),
            'breeding' => BreedingRecord::factory()->create(),
        ];

        foreach ($records as $tab => $record) {
            $record->delete();

            Livewire::test(Index::class)
                ->set('tab', $tab)
                ->call('confirmRestore', $tab, $record->id)
                ->call('restore');

            $this->assertNotSoftDeleted($record);
        }
    }

    public function test_a_restored_photo_becomes_the_main_photo_when_the_bird_has_none(): void
    {
        $this->actingAs($this->owner());
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        app(ArchivePhoto::class)->handle($photo);

        Livewire::test(Index::class)
            ->set('tab', 'photos')
            ->call('confirmRestore', 'photos', $photo->id)
            ->call('restore');

        $this->assertNotSoftDeleted('broodcock_photos', ['id' => $photo->id]);
        $this->assertTrue($photo->fresh()->is_primary);
    }

    public function test_a_photo_is_not_restored_onto_a_bird_that_is_already_full(): void
    {
        config(['gfms.photos.max_per_broodcock' => 2]);
        $this->actingAs($this->owner());
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();
        app(ArchivePhoto::class)->handle($photo);
        BroodcockPhoto::factory()->count(2)->forBroodcock($bird)->create();

        Livewire::test(Index::class)
            ->set('tab', 'photos')
            ->call('confirmRestore', 'photos', $photo->id)
            ->call('restore')
            ->assertSee('already has the most photos allowed');

        $this->assertSoftDeleted('broodcock_photos', ['id' => $photo->id]);
    }

    public function test_a_record_keeper_cannot_restore_even_by_calling_the_action(): void
    {
        $bird = Broodcock::factory()->create();
        $bird->delete();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Index::class)
            ->assertForbidden();

        $this->assertSoftDeleted($bird);
    }

    // -----------------------------------------------------------------
    // Archived photos
    // -----------------------------------------------------------------

    /** The owner sees an archived photo's thumbnail in the Archive; nobody else can load it. */
    public function test_only_the_owner_can_load_an_archived_photo(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();
        Storage::disk($photo->disk)->put($photo->path, 'bytes');
        app(ArchivePhoto::class)->handle($photo);

        $this->actingAs($this->owner())->get(route('photos.show', $photo))->assertOk();
        $this->actingAs(User::factory()->staff()->create())->get(route('photos.show', $photo))->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Band numbers
    // -----------------------------------------------------------------

    /** An archived bird keeps its band, so a new bird cannot take it - and is told why. */
    public function test_a_new_bird_cannot_take_an_archived_birds_band_number(): void
    {
        $this->actingAs($this->owner());
        Broodcock::factory()->create(['name' => 'Reyna', 'band_number' => 'SW-1002'])->delete();

        Livewire::test(Form::class)
            ->set('name', 'Newcomer')
            ->set('band_number', 'SW-1002')
            ->call('save')
            ->assertHasErrors(['band_number'])
            ->assertSee('The archived bird Reyna still has that band number.');
    }

    /** Sanity: archived death records stay counted by the database's one-per-bird rule. */
    public function test_archived_records_stay_in_the_database(): void
    {
        $record = MortalityRecord::factory()->create();
        $record->delete();

        $this->assertSame(1, MortalityRecord::withTrashed()->count());
    }
}
