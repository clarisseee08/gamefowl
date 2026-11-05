<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Livewire\Broodcocks\Show;
use App\Livewire\Performance\Form;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hens do not fight. Sparring, derbies and conditioning are a broodcock's
 * record, so a broodhen has no Performance tab, is not offered on the
 * performance form, and a record for one is refused.
 */
final class HensHaveNoPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hens_page_has_no_performance_tab(): void
    {
        $hen = Broodcock::factory()->female()->create();
        $cock = Broodcock::factory()->male()->create();
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)->test(Show::class, ['broodcock' => $hen])
            ->assertDontSee("\$set('tab', 'performance')", escape: false);

        Livewire::actingAs($owner)->test(Show::class, ['broodcock' => $cock])
            ->assertSee("\$set('tab', 'performance')", escape: false);
    }

    public function test_an_old_performance_link_to_a_hen_opens_the_pedigree(): void
    {
        $hen = Broodcock::factory()->female()->create();

        Livewire::withQueryParams(['tab' => 'performance'])
            ->actingAs(User::factory()->owner()->create())
            ->test(Show::class, ['broodcock' => $hen])
            ->assertSet('tab', 'overview');
    }

    public function test_the_performance_form_offers_broodcocks_only(): void
    {
        $hen = Broodcock::factory()->female()->create(['name' => 'Henrietta']);
        $cock = Broodcock::factory()->male()->create(['name' => 'Tandang']);

        $birds = Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->instance()->birdOptions->pluck('id');

        $this->assertContains($cock->id, $birds);
        $this->assertNotContains($hen->id, $birds);
    }

    public function test_a_performance_record_for_a_hen_is_refused(): void
    {
        $hen = Broodcock::factory()->female()->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Form::class)
            ->set('broodcock_id', (string) $hen->id)
            ->set('event_date', '2026-05-01')
            ->set('event_type', PerformanceEventType::Derby->value)
            ->set('result', PerformanceResult::Win->value)
            ->call('save')
            ->assertHasErrors(['broodcock_id']);

        $this->assertDatabaseCount('performance_records', 0);
    }
}
