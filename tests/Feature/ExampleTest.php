<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test for the application entry point.
 *
 * "/" used to redirect everyone to the dashboard, on the reasoning that this
 * system had no anonymous content. It has anonymous content now: the catalogue
 * is the farm's public advertisement, so the first thing the site says to a
 * member of the public is the stock, not a login form.
 *
 * Staff still land on the dashboard, because that is where their working day
 * starts. The route reads the role rather than picking one and being wrong for
 * half the people who open it.
 */
final class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_sends_a_visitor_to_the_catalogue(): void
    {
        $this->get('/')->assertRedirect(route('catalog.index'));

        $this->followingRedirects()
            ->get('/')
            ->assertOk()
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
