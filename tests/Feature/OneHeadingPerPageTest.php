<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One page, one h1.
 *
 * WHY THIS EXISTS. The public front page shipped two. The farm's nameplate is
 * the page's heading, and the catalogue grid embedded below it rendered its own
 * "Our Gamefowl" h1 - correct when Browse is routed on its own at /catalog, and
 * wrong when it is a section of a larger page.
 *
 * docs/redesign/gaps.md recorded this during the migration and left it standing,
 * which is how it survived: written down is not fixed.
 *
 * It is not a tidiness complaint. Listing a page's headings is how a screen
 * reader user finds their way around it, and two h1s give two competing answers
 * to "what is this page" when the second is a section of the first. The
 * document title, which every page now sets, is the other half of that same
 * wayfinding - see EveryPageNamesItselfTest.
 */
final class OneHeadingPerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_a_guest_can_reach_has_exactly_one_h1(): void
    {
        $bird = Broodcock::factory()->create(['name' => 'Bagwis']);

        $this->assertOneHeadingOn([
            route('home'),
            route('catalog.index'),
            route('broodcocks.show', $bird),
            route('broodcocks.pedigree', $bird),
            route('login'),
        ]);
    }

    public function test_every_console_page_has_exactly_one_h1(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        Broodcock::factory()->create();

        $this->assertOneHeadingOn([
            route('dashboard'),
            route('broodcocks.index'),
            route('health.index'),
            route('health.schedule'),
            route('breeding.index'),
            route('performance.index'),
            route('mortality.index'),
            route('reports.index'),
            route('users.index'),
            route('settings.edit'),
            route('profile.edit'),
        ]);
    }

    /**
     * @param  list<string>  $urls
     */
    private function assertOneHeadingOn(array $urls): void
    {
        $offenders = [];

        foreach ($urls as $url) {
            $response = $this->get($url);

            if ($response->getStatusCode() !== 200) {
                continue;   // reachability is NoDoorYouCannotOpenTest's job
            }

            $count = preg_match_all('/<h1[\s>]/i', $response->getContent() ?: '');
            $path = parse_url($url, PHP_URL_PATH) ?: $url;

            if ($count !== 1) {
                $offenders[] = sprintf('%s has %d <h1>', $path, $count);
            }
        }

        $this->assertSame([], $offenders,
            "A page needs exactly one h1. A component that renders its own heading and is\n"
            ."also embedded in a larger page has to take its level from the page it is on:\n  "
            .implode("\n  ", $offenders));
    }
}
