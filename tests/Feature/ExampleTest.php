<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test for the application entry point.
 *
 * "/" is a redirect to the dashboard rather than a public landing page - this
 * system has no anonymous content, so an unauthenticated visitor is sent to
 * the sign-in screen.
 */
final class ExampleTest extends TestCase
{
    public function test_the_root_url_redirects_guests_to_sign_in(): void
    {
        $this->get('/')->assertRedirect('/dashboard');

        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Sign in to your account');
    }
}
