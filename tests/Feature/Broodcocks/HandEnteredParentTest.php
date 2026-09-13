<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Enums\Sex;
use App\Livewire\Broodcocks\Form;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Recording a parent that is not on the list.
 *
 * The dropdowns can only offer birds already on record, which left a keeper no
 * way to enter a bird the farm does not own - bought in already mated, borrowed
 * for a season, or simply never registered. Their only options were to leave
 * the pedigree blank or to invent a farm bird, and both corrupt the one feature
 * this system exists for.
 *
 * A typed name becomes a REAL broodcock row flagged is_external, not a free-text
 * column. sire_id and dam_id are foreign keys: a name stored as text leaves the
 * id NULL and cuts the family tree off above that bird. This is the same shape
 * the breeding form already used, now sharing one implementation.
 */
final class HandEnteredParentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    /** @return array<string, mixed> */
    private function validBird(): array
    {
        return [
            'name' => 'Bagwis',
            'class' => 'ordinary',
            'sex' => 'male',
            'status' => 'active',
        ];
    }

    private function form(): Testable
    {
        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    // -----------------------------------------------------------------
    // Creating
    // -----------------------------------------------------------------

    public function test_a_sire_can_be_typed_in_when_it_is_not_on_the_list(): void
    {
        $this->form()
            ->set('sire_is_external', true)
            ->set('sire_external_name', 'Visiting Cock')
            ->set('sire_external_bloodline', 'Sweater')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', [
            'name' => 'Visiting Cock',
            'sex' => Sex::Male->value,
            'bloodline' => 'Sweater',
            'is_external' => true,
        ]);

        // The point of creating a row rather than storing a name: the bird
        // still POINTS at a parent, so the pedigree can walk through it.
        $sire = Broodcock::query()->where('name', 'Visiting Cock')->sole();
        $this->assertSame($sire->id, Broodcock::query()->where('name', 'Bagwis')->sole()->sire_id);
    }

    public function test_a_dam_can_be_typed_in_when_she_is_not_on_the_list(): void
    {
        $this->form()
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Ilocos Hen')
            ->call('save')
            ->assertHasNoErrors();

        $dam = Broodcock::query()->where('name', 'Ilocos Hen')->sole();

        $this->assertSame(Sex::Female->value, $dam->sex->value);
        $this->assertTrue($dam->is_external);
        $this->assertSame($dam->id, Broodcock::query()->where('name', 'Bagwis')->sole()->dam_id);
    }

    public function test_both_parents_can_be_typed_in(): void
    {
        $this->form()
            ->set('sire_is_external', true)
            ->set('sire_external_name', 'Outside Cock')
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Outside Hen')
            ->call('save')
            ->assertHasNoErrors();

        $bird = Broodcock::query()->where('name', 'Bagwis')->sole();

        $this->assertNotNull($bird->sire_id);
        $this->assertNotNull($bird->dam_id);
        $this->assertSame(2, Broodcock::query()->external()->count());
    }

    public function test_choosing_from_the_list_still_works(): void
    {
        $sire = Broodcock::factory()->male()->create(['name' => 'Agila']);

        $this->form()
            ->set('sire_id', (string) $sire->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($sire->id, Broodcock::query()->where('name', 'Bagwis')->sole()->sire_id);
        // And nothing extra was invented.
        $this->assertSame(0, Broodcock::query()->external()->count());
    }

    // -----------------------------------------------------------------
    // Not duplicating the same outside bird
    // -----------------------------------------------------------------

    /**
     * Otherwise one borrowed hen becomes several separate nodes in the pedigree
     * and the tree quietly stops meaning anything.
     */
    public function test_naming_the_same_outside_hen_twice_reuses_her_row(): void
    {
        foreach (['Bagwis', 'Dagitab'] as $chick) {
            $component = Livewire::actingAs($this->staff())->test(Form::class);

            foreach (['name' => $chick] + $this->validBird() as $field => $value) {
                $component->set($field, $value);
            }

            $component->set('dam_is_external', true)
                ->set('dam_external_name', 'Ilocos Hen')
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(1, Broodcock::query()->where('name', 'Ilocos Hen')->count());
    }

    public function test_surrounding_whitespace_does_not_create_a_second_row(): void
    {
        foreach ([['Bagwis', 'Ilocos Hen'], ['Dagitab', '  Ilocos Hen  ']] as [$chick, $typed]) {
            $component = Livewire::actingAs($this->staff())->test(Form::class);

            foreach (['name' => $chick] + $this->validBird() as $field => $value) {
                $component->set($field, $value);
            }

            $component->set('dam_is_external', true)
                ->set('dam_external_name', $typed)
                ->call('save')
                ->assertHasNoErrors();
        }

        $this->assertSame(1, Broodcock::query()->where('name', 'Ilocos Hen')->count());
    }

    /** An outside bird recorded through either form is the same bird. */
    public function test_it_reuses_a_bird_created_by_the_breeding_form(): void
    {
        $existing = Broodcock::factory()->external()->create([
            'name' => 'Ilocos Hen',
            'sex' => Sex::Female,
        ]);

        $this->form()
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Ilocos Hen')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Broodcock::query()->where('name', 'Ilocos Hen')->count());
        $this->assertSame($existing->id, Broodcock::query()->where('name', 'Bagwis')->sole()->dam_id);
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    public function test_a_typed_parent_still_needs_a_name(): void
    {
        $this->form()
            ->set('sire_is_external', true)
            ->set('sire_external_name', '')
            ->call('save')
            ->assertHasErrors('sire_external_name');

        $this->assertDatabaseMissing('broodcocks', ['name' => 'Bagwis']);
    }

    /**
     * Ticking the box must not become a way around the ancestor-loop rule.
     *
     * The validation rule cannot catch this case - when the box is ticked there
     * is no id to check, only a name - and firstOrCreate can return an EXISTING
     * outside bird that has since been given parents of its own. So the check
     * runs again on the resolved bird.
     */
    public function test_a_typed_parent_cannot_be_a_descendant_of_the_bird(): void
    {
        $ancestor = Broodcock::factory()->male()->create(['name' => 'Agila']);

        // An outside bird that is, unusually, descended from the bird we are
        // about to edit.
        Broodcock::factory()->external()->create([
            'name' => 'Wandering Cock',
            'sex' => Sex::Male,
            'sire_id' => $ancestor->id,
        ]);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $ancestor])
            ->set('sire_is_external', true)
            ->set('sire_external_name', 'Wandering Cock')
            ->call('save')
            ->assertHasErrors('sire_external_name');

        $this->assertNull($ancestor->fresh()->sire_id);
    }

    // -----------------------------------------------------------------
    // The outside bird must not become farm stock
    // -----------------------------------------------------------------

    /**
     * A bird created this way is a pedigree node, not livestock. It must stay
     * out of the counts, the catalogue and the public site - which is what the
     * is_external flag is for.
     */
    public function test_a_typed_parent_is_not_counted_as_farm_stock(): void
    {
        $this->form()
            ->set('dam_is_external', true)
            ->set('dam_external_name', 'Ilocos Hen')
            ->call('save')
            ->assertHasNoErrors();

        $dam = Broodcock::query()->where('name', 'Ilocos Hen')->sole();

        $this->assertFalse($dam->isPubliclyVisible());
        $this->assertFalse(
            Broodcock::query()->farmStock()->whereKey($dam->id)->exists(),
            'A bird the farm does not own was counted as its stock.'
        );
    }
}
