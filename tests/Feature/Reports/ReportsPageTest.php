<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Reports\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Download a Report card: the period is the console's own dropdown in
 * the card's header, with a custom range behind "Custom range…", and each
 * report is a tile with its PDF and CSV downloads.
 */
final class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_period_is_a_dropdown_with_a_custom_range(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Download a Report')
            ->assertSeeInOrder(['All time', 'This month', 'Last 3 months', 'Last 12 months', 'This year', 'Custom range…'])
            ->assertSee('id="report-period"', false)
            ->assertSee('Covering');
    }

    public function test_the_old_chip_row_and_its_note_are_gone(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('reports.index'))
            ->assertDontSee('The period is saved with each report')
            ->assertDontSee('Reports below will cover');
    }

    public function test_every_report_offers_both_downloads(): void
    {
        $response = $this->actingAs(User::factory()->staff()->create())->get(route('reports.index'));

        foreach (app(ReportRegistry::class)->keys() as $key) {
            $response->assertSee(route('reports.pdf', $key), false)
                ->assertSee(route('reports.csv', $key), false);
        }
    }

    public function test_a_period_in_the_address_opens_on_the_custom_range(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('reports.index', ['from' => '2026-01-01', 'to' => '2026-06-30']))
            ->assertOk()
            ->assertSee("preset: 'custom'", false);
    }
}
