<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test for the application entry point.
 *
 * "/" has been three things. It redirected everyone to the dashboard, on the
 * reasoning that the system had no anonymous content. Then it redirected the
 * public to the catalogue, once the catalogue became the farm's advertisement.
 * It is now a page in its own right: a redirect is a fine answer to "where
 * should this person go", but it is a poor front door, and a visitor arriving
 * at a grid of birds was never told whose farm it was or how to reach it.
 *
 * Staff still go to the dashboard, because that is where their working day
 * starts. The page reads the role rather than picking one and being wrong for
 * half the people who open it.
 */
final class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_gives_a_visitor_the_farms_front_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(config('gfms.farm.name'))
            // No login wall, and the shop-window shell rather than the console.
            ->assertSee('Sign in');
    }

    public function test_the_root_url_sends_staff_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
