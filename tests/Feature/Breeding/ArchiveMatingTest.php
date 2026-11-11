<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Actions\Breeding\GenerateOffspring;
use App\Livewire\Breeding\Show;
use App\Models\BreedingRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A mating can be archived by the owner, like every other farm record.
 *
 * Matings had no way out at all - a wrong entry stayed in the breeding figures
 * for good. Archiving hides it and keeps it, so the owner can restore it from
 * the Archive.
 */
final class ArchiveMatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_is_offered_archive_on_a_mating(): void
    {
        $record = BreedingRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Show::class, ['record' => $record])
            ->assertSee('Archive')
            ->call('confirmArchive')
            ->assertSet('confirmingArchive', true)
            ->assertSee('Archive this breeding record?')
            ->assertSee('restore it from the Archive')
            ->call('cancelArchive')
            ->assertSet('confirmingArchive', false);

        $this->assertNotSoftDeleted($record);
    }

    public function test_archiving_hides_the_mating_and_keeps_it(): void
    {
        $record = BreedingRecord::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Show::class, ['record' => $record])
            ->call('confirmArchive')
            ->call('archive')
            ->assertRedirect(route('breeding.index'));

        $this->assertSoftDeleted($record);
        $this->assertSame(0, BreedingRecord::query()->count());
    }

    /** The chicks are birds in their own right; archiving the mating leaves them alone. */
    public function test_archiving_a_mating_keeps_its_registered_chicks(): void
    {
        $record = BreedingRecord::factory()->create([
            'eggs_set' => 6, 'eggs_fertile' => 5, 'eggs_hatched' => 4, 'offspring_count' => 0,
        ]);
        $chicks = app(GenerateOffspring::class)->handle($record, 2);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Show::class, ['record' => $record])
            ->call('archive');

        foreach ($chicks as $chick) {
            $this->assertNotSoftDeleted($chick);
        }
    }

    public function test_a_record_keeper_cannot_archive_a_mating(): void
    {
        $record = BreedingRecord::factory()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['record' => $record])
            ->assertDontSee('Archive this breeding record?')
            ->call('archive')
            ->assertForbidden();

        $this->assertNotSoftDeleted($record);
    }
}
