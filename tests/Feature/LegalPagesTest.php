<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

/**
 * The privacy notice and the terms of use.
 *
 * They exist because a public website that collects anything at all - even a
 * session cookie - should say so somewhere a visitor can actually reach, and
 * this site had neither page nor link. The brief asked for real pages inside
 * the site rather than links out to a PDF, and these tests hold that: a PDF
 * cannot be fetched by route name, and a 404 cannot be asserted against.
 *
 * WHAT THESE TESTS ARE REALLY FOR. The content is a statement about what this
 * application does, checked against the code rather than copied from a
 * template. The assertions below pin the claims that would quietly become
 * FALSE if the system changed - public registration appearing, a contact form
 * being added back, the catalogue closing behind a login. If one of them
 * fails, the page is lying and needs rewriting, which is the only way a
 * privacy notice is worth having.
 */
final class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_read_the_privacy_notice(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy notice');
    }

    public function test_a_guest_can_read_the_terms(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of use');
    }

    /** Both are linked from the footer, which is on every public page. */
    public function test_both_pages_are_reachable_from_the_front_page(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }

    /**
     * The privacy notice states there is no self-registration. Fortify's
     * feature list is what makes that true, so assert the claim against the
     * mechanism rather than against the sentence.
     */
    public function test_the_claim_that_nobody_can_register_themselves_is_still_true(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('only the owner can');

        $this->assertNotContains(
            Features::registration(),
            (array) config('fortify.features'),
            'The privacy notice tells visitors they cannot register themselves. '
            .'Fortify registration is now enabled, so that sentence is false.'
        );
    }

    /**
     * It also states the catalogue needs no account. That was true when it was
     * written and is the kind of thing a later change closes without noticing.
     */
    public function test_the_claim_that_browsing_needs_no_account_is_still_true(): void
    {
        $this->get(route('catalog.index'))->assertOk();
        $this->get(route('home'))->assertOk();
    }

    /**
     * A deactivated account is sent to the login screen everywhere else, and a
     * legal page is not the place to make an exception - otherwise these two
     * routes become the one way a disabled account still reaches the site.
     */
    public function test_a_deactivated_account_is_sent_to_sign_in(): void
    {
        $this->actingAs(User::factory()->staff()->inactive()->create())
            ->get(route('legal.privacy'))
            ->assertRedirect(route('login'));
    }
}
