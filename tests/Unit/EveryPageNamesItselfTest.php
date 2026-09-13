<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Catalog\Browse;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use ReflectionClass;
use Tests\TestCase;

/**
 * Every routed page says what it is.
 *
 * WHY THIS EXISTS. Eighteen of the twenty-two routed components set no title at
 * all, so they fell through to the layout default and the console reported
 * itself as "Dashboard" on nearly every screen. Broodcocks, Breeding,
 * Performance, Reports, Users - index and form alike - were all "Dashboard" in
 * the tab, in the history menu, and in the bookmark a keeper saves.
 *
 * It is not only cosmetic. The document title is what a screen reader announces
 * on every wire:navigate, so a blind user moving through the console was told
 * "Dashboard" seven times in a row while the content changed underneath them.
 *
 * Nothing caught it because a missing title is not an error: the layout's
 * `$title ?? 'Dashboard'` fallback is a perfectly valid expression that renders
 * a perfectly valid page. The only way to see it is to look at the tab, which
 * is exactly the kind of thing nobody does while building a page.
 */
final class EveryPageNamesItselfTest extends TestCase
{
    /**
     * Components that are routed but are not pages in their own right.
     *
     * Catalog\Browse is the catalogue grid. It is routed so it can be linked
     * to, but it also renders INSIDE the landing page, and its own source says
     * it deliberately sets neither a layout nor a title so that it inherits
     * whichever shell it finds itself in.
     *
     * @var list<class-string>
     */
    private const NOT_A_PAGE = [
        Browse::class,
    ];

    public function test_every_routed_livewire_component_sets_a_title(): void
    {
        $untitled = [];
        $inspected = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $component = $route->getAction('livewire_component');

            if ($component === null || in_array($component, self::NOT_A_PAGE, true)) {
                continue;
            }

            $inspected++;

            if (! $this->namesItself($component)) {
                $untitled[] = sprintf('%s (%s)', $component, '/'.ltrim($route->uri(), '/'));
            }
        }

        /*
         * Guard against a vacuous pass. `livewire_component` is an internal
         * Livewire action key, not a public contract - if a future version
         * stores the class somewhere else this loop would quietly inspect
         * nothing and report success forever, which is the worst thing a guard
         * test can do. The floor is well under the current count so ordinary
         * route changes do not trip it.
         */
        $this->assertGreaterThan(15, $inspected,
            'This test found almost no routed Livewire components, which means it is no '
            .'longer reading them correctly rather than that they are all fine.');

        $this->assertSame([], array_values(array_unique($untitled)),
            "These pages fall through to the layout's default title, so the browser tab,\n"
            ."the history menu and every screen-reader page announcement call them\n"
            ."something they are not. Add #[Title] or ->title() in render():\n  "
            .implode("\n  ", array_unique($untitled)));
    }

    /**
     * A component names itself with either the #[Title] attribute or a
     * ->title() call on the view it returns.
     *
     * Both are checked because this application genuinely uses both, and for a
     * reason: #[Title] takes a constant, so any page whose name depends on the
     * record it is showing - a bird, a mating, an edit form - has to use
     * ->title() instead.
     *
     * @param  class-string  $component
     */
    private function namesItself(string $component): bool
    {
        $reflection = new ReflectionClass($component);

        if ($reflection->getAttributes(Title::class) !== []) {
            return true;
        }

        $file = $reflection->getFileName();

        return $file !== false && str_contains((string) file_get_contents($file), '->title(');
    }
}
