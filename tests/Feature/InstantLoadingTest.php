<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What app.js needs to answer a click before the server does.
 *
 * A page change hides [data-page-content] and copies in one of the four page
 * skeletons the layout keeps as templates. If either is missing, the script
 * quietly does nothing and the console goes back to freezing on every click -
 * so their presence on the console layout is pinned here. The behaviour itself
 * (which skeleton for which address, only the last click counting) lives in
 * resources/js/app.js.
 */
final class InstantLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_console_layout_carries_the_page_content_marker_and_every_skeleton(): void
    {
        $response = $this->actingAs(User::factory()->owner()->create())->get(route('dashboard'));

        $response->assertOk()->assertSee('data-page-content', false);

        foreach (['list', 'detail', 'form', 'dashboard'] as $kind) {
            $response->assertSee('data-page-skeleton-template="'.$kind.'"', false);
        }
    }

    public function test_a_skeleton_announces_itself_once_and_hides_its_bars(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('dashboard'))
            ->assertSee('Loading the page&hellip;', false)
            ->assertSee('aria-busy="true"', false);
    }
}
