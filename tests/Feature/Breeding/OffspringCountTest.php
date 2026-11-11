<?php

declare(strict_types=1);

namespace Tests\Feature\Breeding;

use App\Actions\Breeding\GenerateOffspring;
use App\Enums\Sex;
use App\Livewire\Breeding\Form;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * How many chicks of a hatch are registered as birds is counted by the system.
 *
 * The mating form used to have a typeable "Offspring Registered" box. The
 * Register chicks button only appears while eggs hatched exceeds that number,
 * so typing the hatched count into it hid the button for good: no chick could
 * ever be registered, and the sire's Offspring tab stayed empty. Only
 * GenerateOffspring, which creates the birds, may move the count.
 */
final class OffspringCountTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    /**
     * A mating with 4 hatched chicks whose dates the edit form accepts: the
     * factory's random dates can put a mating before its sire hatched, which
     * the form rightly refuses on save.
     */
    private function hatch(): BreedingRecord
    {
        return BreedingRecord::factory()->create([
            'sire_id' => Broodcock::factory()->create(['sex' => Sex::Male, 'date_hatched' => today()->subYears(3)])->id,
            'dam_id' => Broodcock::factory()->create(['sex' => Sex::Female, 'date_hatched' => today()->subYears(3)])->id,
            'mating_date' => today()->subMonths(2),
            'eggs_set' => 8, 'eggs_fertile' => 6, 'eggs_hatched' => 4, 'offspring_count' => 0,
        ]);
    }

    public function test_the_mating_form_does_not_ask_for_offspring_registered(): void
    {
        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->assertDontSee('Offspring Registered')
            // and tells the user where the chicks are added instead
            ->assertSee('use Register chicks on the next page');
    }

    public function test_a_new_mating_starts_with_no_registered_chicks_and_offers_to_register_them(): void
    {
        $sire = Broodcock::factory()->create(['sex' => Sex::Male]);
        $dam = Broodcock::factory()->create(['sex' => Sex::Female]);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('sire_id', (string) $sire->id)
            ->set('dam_id', (string) $dam->id)
            ->set('mating_date', today()->subDays(30)->toDateString())
            ->set('eggs_set', 8)
            ->set('eggs_fertile', 6)
            ->set('eggs_hatched', 4)
            ->call('save')
            ->assertHasNoErrors();

        $record = BreedingRecord::query()->sole();

        $this->assertSame(0, $record->offspring_count);
        $this->assertSame(4, $record->unregisteredOffspring());
        $this->assertTrue($record->hasUnregisteredOffspring());
    }

    /** Registered chicks are birds that exist; editing the mating must not overwrite their count. */
    public function test_editing_a_mating_keeps_the_count_of_registered_chicks(): void
    {
        $record = $this->hatch();
        app(GenerateOffspring::class)->handle($record, 2);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['record' => $record->fresh()])
            ->set('notes', 'Two chicks still to band.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $record->fresh()->offspring_count);
        $this->assertSame(2, $record->sire->offspring()->count(), 'The chicks must stay on the sire.');
    }

    public function test_eggs_hatched_cannot_go_below_the_chicks_already_registered(): void
    {
        $record = $this->hatch();
        app(GenerateOffspring::class)->handle($record, 3);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['record' => $record->fresh()])
            ->set('eggs_hatched', 2)
            ->call('save')
            ->assertHasErrors(['eggs_hatched'])
            ->assertSee('3 chicks from this hatch are already registered');

        $this->assertSame(4, $record->fresh()->eggs_hatched);
    }

    /** The edit form can still lower hatched eggs to exactly the registered count. */
    public function test_eggs_hatched_may_equal_the_chicks_already_registered(): void
    {
        $record = $this->hatch();
        app(GenerateOffspring::class)->handle($record, 3);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['record' => $record->fresh()])
            ->set('eggs_hatched', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, $record->fresh()->eggs_hatched);
        $this->assertFalse($record->fresh()->hasUnregisteredOffspring());
    }
}
