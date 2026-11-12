<?php

declare(strict_types=1);

namespace Tests\Feature\Mortality;

use App\Livewire\Mortality\Form;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cause of Death and How Was the Bird Disposed Of? are the system's own
 * dropdowns, like every other one, not the browser's suggestion list. Each
 * option explains itself in brackets, and "Other" opens a text box for an
 * answer of the user's own. What is saved is the short name, never the
 * explanation, so the mortality report groups the same cause together.
 */
final class CauseAndDisposalChoiceTest extends TestCase
{
    use RefreshDatabase;

    private function form(): Testable
    {
        $bird = Broodcock::factory()->create(['date_hatched' => today()->subYears(2)]);

        return Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $bird->id)
            ->set('date_of_death', today()->toDateString());
    }

    public function test_the_options_explain_themselves_and_the_browser_list_is_gone(): void
    {
        $this->form()
            ->assertSee('Disease (an illness or infection)')
            ->assertSee('Fighting (wounds from a fight or sparring)')
            ->assertSee('Discarded (thrown out with farm waste)')
            ->assertSee('Other (type your own)')
            ->assertDontSee('Composted')
            ->assertDontSee('<datalist', false);
    }

    public function test_a_listed_choice_saves_its_short_name(): void
    {
        $this->form()
            ->set('cause_choice', 'Fighting')
            ->set('disposal_choice', 'Discarded')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mortality_records', ['cause_of_death' => 'Fighting', 'disposal_method' => 'Discarded']);
    }

    public function test_other_opens_a_text_box_and_saves_what_was_typed(): void
    {
        $this->form()
            ->assertDontSee('Describe the cause of death')
            ->set('cause_choice', Form::OTHER)
            ->assertSee('Describe the cause of death')
            ->set('cause_of_death', 'Snake bite in the pen')
            ->set('disposal_choice', Form::OTHER)
            ->assertSee('Describe how the bird was disposed of')
            ->set('disposal_method', 'Given to the neighbour')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mortality_records', [
            'cause_of_death' => 'Snake bite in the pen',
            'disposal_method' => 'Given to the neighbour',
        ]);
    }

    public function test_other_with_nothing_typed_still_needs_a_cause(): void
    {
        $this->form()
            ->set('cause_choice', 'Disease')
            ->set('cause_choice', Form::OTHER)
            ->assertSet('cause_of_death', '')
            ->call('save')
            ->assertHasErrors(['cause_of_death']);
    }

    public function test_the_disposal_may_be_left_for_later(): void
    {
        $this->form()
            ->set('cause_choice', 'Old age')
            ->set('disposal_choice', 'Buried')
            ->set('disposal_choice', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mortality_records', ['cause_of_death' => 'Old age', 'disposal_method' => null]);
    }
}
