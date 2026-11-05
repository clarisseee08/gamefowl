<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Form;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Weight, colour, comb type and leg colour are dropdowns of the farm's usual
 * values with an "Others" box. The choice binds without a round trip, so it
 * must reach the saved columns at Save - and an old value that is not on the
 * list must open under "Others" with its text intact.
 */
final class BroodcockFormPresetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listed_values_and_others_text_are_saved(): void
    {
        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('name', 'Preset Bird')
            ->set('weight_choice', '2.2')
            ->set('color_choice', Form::$presetOther)
            ->set('color', 'Mottled')
            ->set('comb_type_choice', 'Pea')
            ->set('leg_color_choice', '')
            ->call('save')
            ->assertHasNoErrors();

        $bird = Broodcock::query()->where('name', 'Preset Bird')->sole();
        $this->assertSame(2.2, (float) $bird->weight);
        $this->assertSame('Mottled', $bird->color);
        $this->assertSame('Pea', $bird->comb_type);
        $this->assertNull($bird->leg_color);
    }

    public function test_an_unlisted_saved_value_opens_under_others_with_its_text(): void
    {
        $bird = Broodcock::factory()->create(['weight' => 2.01, 'color' => 'Mottled', 'comb_type' => 'Pea', 'leg_color' => null]);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class, ['broodcock' => $bird])
            ->assertSet('weight_choice', Form::$presetOther)
            ->assertSet('weight', '2.01')
            ->assertSet('color_choice', Form::$presetOther)
            ->assertSet('color', 'Mottled')
            ->assertSet('comb_type_choice', 'Pea')
            ->assertSet('leg_color_choice', '');
    }

    public function test_a_listed_weight_matches_whatever_the_database_returns(): void
    {
        $bird = Broodcock::factory()->create(['weight' => 2.0]);

        Livewire::actingAs(User::factory()->owner()->create())
            ->test(Form::class, ['broodcock' => $bird])
            ->assertSet('weight_choice', '2.0');
    }
}
