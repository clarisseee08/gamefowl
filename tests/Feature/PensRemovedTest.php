<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Sex;
use App\Livewire\Breeding\Show;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pens were removed completely on 18 Nov 2026 (migration drop_pens).
 *
 * Their screens had gone in September while the table, broodcocks.pen_id, a
 * Pen dropdown on Register chicks and a report column stayed - a half-removal
 * the documents then kept describing. These pin the whole removal, so a pen
 * cannot quietly come back as data with no screen again.
 */
final class PensRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_and_the_column_are_gone(): void
    {
        $this->assertFalse(Schema::hasTable('pens'));
        $this->assertFalse(Schema::hasColumn('broodcocks', 'pen_id'));
    }

    public function test_there_is_no_pen_model(): void
    {
        $this->assertFalse(class_exists('App\\Models\\Pen'));
        $this->assertFalse(method_exists(Broodcock::class, 'pen'));
    }

    public function test_registering_chicks_asks_for_no_pen(): void
    {
        $record = BreedingRecord::factory()->withUnregisteredOffspring(2)->create();

        Livewire::actingAs(User::factory()->staff()->create())
            ->test(Show::class, ['record' => $record])
            ->call('startGenerating')
            ->assertDontSee('generatePenId', false)
            ->assertDontSee('Not assigned')
            ->set('generateSex', Sex::Male->value)
            ->call('generate')
            ->assertHasNoErrors();

        $this->assertSame(2, $record->fresh()->offspring_count);
    }
}
