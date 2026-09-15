<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A colour utility used in a view must exist in the compiled stylesheet.
 *
 * THE FAILURE THIS CATCHES IS SILENT, WHICH IS WHY IT NEEDS A TEST. Tailwind
 * emits nothing for a utility it does not recognise: no CSS, no warning, no
 * build failure. `bg-pearl` in a view is not an error - it is a class that
 * styles nothing, and it looks exactly like a designer forgot to set a
 * background.
 *
 * This is not hypothetical. A 2026-09-14 audit found FOURTEEN token names in
 * this project's own design documents that compile to nothing - bg-parchment,
 * bg-canvas, bg-pearl, text-ink, text-ink-80, text-ink-48, border-hairline,
 * border-rule-strong, text-action, bg-action, text-ok, bg-ok-wash and the band
 * names crimson/forest/slate - and two of them had already shipped into
 * livewire/appointments/index.blade.php, where a table head rendered with no
 * ground at all while the file's own comment promised 7:1 contrast. (That view
 * has since been deleted with the rest of the visit-request feature; the audit
 * is quoted here as the reason this guard exists, not as a live reference.)
 *
 * WHY THE EXISTING GUARDS MISSED IT. BadgeVocabularyTest checks the `badge-`,
 * `btn-` and `input-` prefixes, because those are the class vocabulary the PHP
 * enums return. DesignSystemGuardTest checks the opposite direction - that no
 * STOCK palette class is used. Neither asks whether a project-specific colour
 * name resolves to anything, and that is the gap every one of the fourteen
 * fell through.
 *
 * THE PRINCIPLE: Tailwind scans the views listed by @source and emits every
 * class it recognises. So a class that is present in a view and absent from
 * the bundle is, by definition, one Tailwind did not recognise.
 */
final class DeadUtilityClassTest extends TestCase
{
    /**
     * Prefixes whose argument is a COLOUR token rather than a size or keyword.
     *
     * @var list<string>
     */
    private const COLOUR_PREFIXES = ['bg', 'text', 'border', 'ring', 'divide', 'fill', 'stroke', 'accent', 'outline', 'decoration', 'caret', 'placeholder'];

    /**
     * Arguments that are keywords, not colours, and so are never tokens.
     *
     * `text-left` and `border-2` share a prefix with `text-primary` and
     * `border-border` but mean something else entirely.
     *
     * @var list<string>
     */
    private const NOT_COLOURS = [
        // text-*
        'left', 'center', 'right', 'justify', 'start', 'end', 'wrap', 'nowrap', 'balance', 'pretty',
        'xs', 'sm', 'base', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', '8xl', '9xl',
        'ellipsis', 'clip',
        // border-*
        'solid', 'dashed', 'dotted', 'double', 'hidden', 'none', 'collapse', 'separate',
        't', 'b', 'l', 'r', 'x', 'y', 's', 'e',
        // bg-*
        'cover', 'contain', 'fixed', 'local', 'scroll', 'repeat', 'auto', 'bottom', 'top',
        'origin', 'clip', 'blend', 'gradient', 'transparent', 'current', 'inherit',
        // shared
        'opacity', 'width', 'style', 'color',
    ];

    private function root(string $path = ''): string
    {
        return __DIR__.'/../../'.$path;
    }

    /** @return list<string> */
    private function views(): array
    {
        $out = [];
        $base = $this->root('resources/views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $path = str_replace('\\', '/', $file->getPathname());

                // dompdf templates are a CSS 2.1 engine with their own plain
                // stylesheet; no Tailwind utility is expected to resolve there.
                if (! str_contains($path, '/reports/pdf/')) {
                    $out[] = $file->getPathname();
                }
            }
        }

        sort($out);

        return $out;
    }

    public function test_every_colour_utility_used_in_a_view_resolves_to_real_css(): void
    {
        $bundles = glob($this->root('public/build/assets/*.css')) ?: [];

        if ($bundles === []) {
            $this->markTestSkipped('No compiled bundle. Run `npm run build` first.');
        }

        $css = '';
        foreach ($bundles as $bundle) {
            $css .= file_get_contents($bundle);
        }

        $prefixes = implode('|', self::COLOUR_PREFIXES);
        $offenders = [];

        foreach ($this->views() as $file) {
            $body = file_get_contents($file);

            if ($body === false) {
                continue;
            }

            // Blade comments are prose and routinely NAME the dead classes in
            // order to warn about them. Stripping them is what stops this test
            // failing on its own documentation.
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body) ?? $body;

            /*
             * ONLY inside a class attribute.
             *
             * Scanning the whole file finds `stroke-linecap` and
             * `stroke-linejoin`, which are SVG presentation ATTRIBUTES on the
             * inline icon paths this project uses instead of an icon package.
             * They share a prefix with a colour utility and are not one.
             *
             * The pattern deliberately also catches :class and x-bind:class,
             * since an Alpine expression carries real class names too.
             */
            preg_match_all('/class\s*=\s*"([^"]*)"|class\s*=\s*\'([^\']*)\'/i', $body, $attributes);
            $haystack = implode(' ', array_merge($attributes[1], $attributes[2]));

            // @class([...]) arrays hold class names as PHP string keys.
            preg_match_all('/@class\(\[(.*?)\]\)/s', $body, $classDirectives);
            $haystack .= ' '.implode(' ', $classDirectives[1]);

            preg_match_all('/\b(?:'.$prefixes.')-[a-z][a-z0-9-]*\b/', $haystack, $matches);

            foreach (array_unique($matches[0]) as $class) {
                $argument = explode('-', $class, 2)[1];

                if (in_array($argument, self::NOT_COLOURS, true)) {
                    continue;
                }

                /*
                 * The class name, with a boundary rather than a leading dot.
                 *
                 * A leading dot misses every variant: `hover:bg-muted` is
                 * emitted as `.hover\:bg-muted:hover`, where the character
                 * before the name is an escaped colon, not a dot. The trailing
                 * boundary is what stops `bg-muted` being satisfied by
                 * `bg-muted-foreground` appearing somewhere in the bundle.
                 */
                if (preg_match('/'.preg_quote($class, '/').'(?![a-z0-9-])/', $css) === 1) {
                    continue;
                }

                $offenders[] = $this->rel($file).': '.$class;
            }
        }

        $this->assertSame([], array_unique($offenders),
            "These colour utilities are used in a view and emit NO CSS at all.\n"
            ."Tailwind does not recognise them, so they style nothing and fail no build.\n"
            ."Check the real token name in resources/css/app.css - the design docs\n"
            ."have carried stale names before:\n  "
            .implode("\n  ", array_unique($offenders)));
    }

    private function rel(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen($this->root())));
    }
}
