<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Which links hand over to Livewire's client-side navigation, and which must not.
 *
 * WHY IT MATTERS HERE MORE THAN USUAL. A plain link reloads the document, which
 * on this deployment means booting PHP, re-reading the session and re-fetching
 * the page against a database roughly 240ms away per query. `wire:navigate`
 * fetches the next page and swaps the body instead, and prefetches on hover, so
 * a sidebar click often resolves against something already in memory.
 *
 * THE EXCLUSION IS THE INTERESTING HALF. `wire:navigate` assumes the response
 * is a page it can swap into the DOM. Point it at an endpoint that returns a
 * file and the download silently stops working - the browser is handed a CSV
 * and asked to treat it as HTML. routes/web.php already records why those
 * endpoints are plain GETs; this is the matching rule for the links to them.
 */
final class NavigationGuardTest extends TestCase
{
    private function view(string $path): string
    {
        $full = __DIR__.'/../../resources/views/'.$path;
        $source = file_get_contents($full);

        if ($source === false) {
            $this->fail("Could not read {$path}.");
        }

        return $source;
    }

    /**
     * Every opening <a> tag in a Blade file.
     *
     * The alternation is not decoration: Blade attributes here contain `=>`
     * inside @class([...]), so a plain [^>]* stops in the middle of an array
     * key and reports a tag that has no href.
     *
     * @return list<string>
     */
    private function anchors(string $source): array
    {
        preg_match_all('/<a\b((?:=>|->|[^>])*)>/s', $source, $matches);

        return $matches[0];
    }

    /** @return list<string> */
    private function anchorsLinkingToRoutes(string $source): array
    {
        return array_values(array_filter(
            $this->anchors($source),
            fn (string $tag): bool => str_contains($tag, 'route('),
        ));
    }

    public function test_every_sidebar_destination_is_navigated(): void
    {
        $tags = $this->anchorsLinkingToRoutes($this->view('components/app-sidebar.blade.php'));

        $this->assertNotEmpty($tags, 'The sidebar should contain links.');

        foreach ($tags as $tag) {
            $this->assertStringContainsString(
                'wire:navigate',
                $tag,
                'A sidebar link reloads the whole document: '.trim(preg_replace('/\s+/', ' ', $tag) ?? ''),
            );
        }
    }

    public function test_the_topbar_breadcrumb_is_navigated(): void
    {
        $tags = $this->anchors($this->view('components/app-topbar.blade.php'));
        $tags = array_values(array_filter($tags, fn (string $tag): bool => str_contains($tag, 'href')));

        $this->assertNotEmpty($tags, 'The topbar should contain a breadcrumb link.');

        foreach ($tags as $tag) {
            $this->assertStringContainsString('wire:navigate', $tag);
        }
    }

    /**
     * The rule that protects the exports.
     *
     * A CSV or PDF endpoint returns a file, not a page. Navigating to it
     * client-side hands the browser a document it cannot swap in, and the
     * download quietly stops happening.
     */
    public function test_report_downloads_are_never_navigated(): void
    {
        $tags = $this->anchors($this->view('livewire/reports/index.blade.php'));

        $downloads = array_values(array_filter(
            $tags,
            fn (string $tag): bool => str_contains($tag, 'reports.csv') || str_contains($tag, 'reports.pdf'),
        ));

        $this->assertCount(2, $downloads, 'Expected a CSV link and a PDF link on the reports screen.');

        foreach ($downloads as $tag) {
            $this->assertStringNotContainsString(
                'wire:navigate',
                $tag,
                'A report download must not be navigated client-side: '.trim(preg_replace('/\s+/', ' ', $tag) ?? ''),
            );
        }
    }

    /**
     * The command palette is navigation too, and it has two ways out.
     *
     * Clicking a result follows the anchor; pressing Enter used to assign
     * window.location, which is a full document load however the anchor is
     * marked up. Both paths now go through the same anchor, so there is one
     * behaviour to reason about rather than two that can drift apart.
     */
    public function test_the_command_palette_navigates_without_reloading(): void
    {
        $source = $this->view('components/command-palette.blade.php');

        // The assignment, not the word: the code comment above go() mentions
        // window.location to explain what it replaced.
        $this->assertDoesNotMatchRegularExpression(
            '/window\.location\s*=/',
            $source,
            'Enter in the palette should follow the result anchor, not reload the document.',
        );

        $this->assertStringContainsString(
            '.click()',
            $source,
            'Enter should activate the highlighted result anchor.',
        );

        $results = array_values(array_filter(
            $this->anchors($source),
            fn (string $tag): bool => str_contains($tag, ':href'),
        ));

        $this->assertNotEmpty($results, 'The palette should render result links.');

        foreach ($results as $tag) {
            $this->assertStringContainsString('wire:navigate', $tag);
        }
    }
}
