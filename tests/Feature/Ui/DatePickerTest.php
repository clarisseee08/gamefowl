<?php

declare(strict_types=1);

namespace Tests\Feature\Ui;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * x-ui.date-picker replaces every <input type="date">. What must hold for the
 * forms built on it: the label still points at the control, the binding still
 * goes on the component (x-modelable), and the limits arrive as dates.
 */
final class DatePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_trigger_takes_the_id_and_the_root_carries_the_binding(): void
    {
        $this->blade('<x-ui.date-picker id="mating_date" wire:model="mating_date" />')
            ->assertSee('id="mating_date"', false)
            ->assertSee('x-modelable="value"', false)
            ->assertSee('wire:model="mating_date"', false)
            ->assertSee('Select date');
    }

    public function test_today_as_a_limit_becomes_todays_date(): void
    {
        $this->blade('<x-ui.date-picker id="d" max="today" />')
            ->assertSee("max: '".today()->toDateString()."'", false);
    }

    public function test_a_moving_limit_follows_an_alpine_expression(): void
    {
        $this->blade('<x-ui.date-picker id="to" x-model="to" min-from="from" />')
            ->assertSee('x-effect="dynMin = (from) || null"', false);
    }

    public function test_a_required_date_offers_no_clear_button(): void
    {
        $this->blade('<x-ui.date-picker id="d" :clearable="false" />')
            ->assertDontSee('Clear the date');
    }

    public function test_no_native_date_field_is_left_in_the_console(): void
    {
        $views = collect(glob(resource_path('views/livewire/**/*.blade.php')) ?: [])
            ->merge(glob(resource_path('views/livewire/*/*.blade.php')) ?: [])
            ->unique();

        foreach ($views as $view) {
            $this->assertStringNotContainsString('type="date"', (string) file_get_contents($view), basename(dirname($view)).'/'.basename($view));
        }

        $this->assertNotEmpty($views);
    }
}
