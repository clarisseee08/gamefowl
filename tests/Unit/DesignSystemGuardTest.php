<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The rules that are cheap to state and expensive to notice being broken.
 *
 * Every one of these exists because it actually happened: a stock palette class
 * shipped in a view, an invented utility rendered as an unstyled pill, a hex was
 * hand-typed into a PDF template, and a centred max-width column made the whole
 * console read as a web page.
 */
final class DesignSystemGuardTest extends TestCase
{
    private function root(string $path = ''): string
    {
        return __DIR__.'/../../'.$path;
    }

    /** @return list<string> */
    private function files(string $dir, string $suffix): array
    {
        $out = [];
        $base = $this->root($dir);

        if (! is_dir($base)) {
            return [];
        }

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
                $out[] = $file->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    private function rel(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen($this->root())));
    }

    /**
     * The stock palette families, assembled at runtime rather than written out.
     *
     * Spelling them literally in this file would compile those exact utilities
     * into the bundle - Tailwind scans the repo for content and cannot tell a
     * test fixture from markup. That is not hypothetical: a design document's
     * own anti-pattern list was doing it.
     *
     * @return list<string>
     */
    private function stockFamilies(): array
    {
        return [
            'gr'.'ay', 'sl'.'ate', 'zi'.'nc', 'st'.'one', 'ne'.'utral',
            'r'.'ed', 'or'.'ange', 'am'.'ber', 'yel'.'low', 'l'.'ime', 'gr'.'een',
            'em'.'erald', 't'.'eal', 'cy'.'an', 's'.'ky', 'bl'.'ue', 'ind'.'igo',
            'vio'.'let', 'pur'.'ple', 'fuch'.'sia', 'p'.'ink', 'r'.'ose',
        ];
    }

    /** §8.1 — no stock Tailwind palette class in any Blade view. */
    public function test_no_view_uses_a_stock_tailwind_palette_class(): void
    {
        $pattern = '/\b(?:bg|text|border|ring|divide|from|via|to|fill|stroke|accent|outline|decoration|shadow)-(?:'
            .implode('|', $this->stockFamilies()).')-\d{2,3}\b/';

        $offenders = [];

        foreach ($this->files('resources/views', '.blade.php') as $file) {
            if (preg_match_all($pattern, file_get_contents($file), $m)) {
                $offenders[] = $this->rel($file).': '.implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders,
            "Views use stock Tailwind palette classes, which this design system does not define:\n"
            .implode("\n", $offenders));
    }

    /** §8.1 — and none survive into the compiled bundle either. */
    public function test_the_compiled_bundle_contains_no_stock_palette_class(): void
    {
        $bundles = glob($this->root('public/build/assets/*.css')) ?: [];

        if ($bundles === []) {
            $this->markTestSkipped('No compiled bundle present. Run `npm run build`.');
        }

        $pattern = '/\.(?:bg|text|border|ring|divide)-(?:'
            .implode('|', $this->stockFamilies()).')-\d{2,3}[\s{,:]/';

        foreach ($bundles as $bundle) {
            if (preg_match_all($pattern, file_get_contents($bundle), $m)) {
                $this->fail(
                    'The production bundle contains stock palette utilities: '
                    .implode(', ', array_unique($m[0]))
                    .'. Something in the content set is generating them — check @source scoping '
                    .'and remember that prose in a scanned file compiles just like markup.'
                );
            }
        }

        $this->assertTrue(true);
    }

    /**
     * §8.3 — no hardcoded hex outside the config and the theme layer.
     *
     * config/gfms-brand.php is the single source of colour; resources/css/app.css
     * mirrors it for Tailwind. Anywhere else, a literal hex is a value that can
     * drift from both without anything noticing.
     */
    public function test_no_hardcoded_hex_outside_the_config_and_the_theme(): void
    {
        $allowed = [
            'config/gfms-brand.php',
            'resources/css/app.css',
        ];

        $offenders = [];
        $targets = array_merge(
            $this->files('resources/views', '.blade.php'),
            $this->files('app', '.php'),
        );

        foreach ($targets as $file) {
            $rel = $this->rel($file);

            if (in_array($rel, $allowed, true)) {
                continue;
            }

            $body = file_get_contents($file);

            // Strip comments first: a hex quoted inside an explanation is
            // documentation, not a value the renderer will ever use.
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body) ?? $body;
            $body = preg_replace('#/\*.*?\*/#s', '', $body) ?? $body;
            $body = preg_replace('#(^|\s)//.*$#m', '', $body) ?? $body;

            if (preg_match_all('/#[0-9A-Fa-f]{6}\b/', $body, $m)) {
                $offenders[] = $rel.': '.implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders,
            "Hardcoded hex colours outside config/gfms-brand.php and the theme layer:\n"
            .implode("\n", $offenders));
    }

    /** §7 — PDF templates must read the config, never carry their own values. */
    public function test_pdf_templates_carry_no_hardcoded_colour(): void
    {
        $offenders = [];

        foreach ($this->files('resources/views/reports/pdf', '.blade.php') as $file) {
            // Strip Blade AND PHP comments. A hex quoted inside an explanation of
            // why the old palette was wrong is documentation, not a value dompdf
            // will ever render - and the sibling hex guard already works this way.
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file)) ?? '';
            $body = preg_replace('#/\*.*?\*/#s', '', $body) ?? $body;
            $body = preg_replace('#(^|\s)//.*$#m', '', $body) ?? $body;

            if (preg_match_all('/#[0-9A-Fa-f]{6}\b/', $body, $m)) {
                $offenders[] = $this->rel($file).': '.implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders,
            'PDF templates must read colour from config/gfms-brand.php. The panel physically '
            ."holds this report; it has to be the same product as the screen:\n"
            .implode("\n", $offenders));
    }

    /**
     * §8.5 — layout containment.
     *
     * The console is an application filling a viewport, not a document centred
     * in a page. A 1280px column on a 1920px monitor leaves 320px of dead margin
     * on each side, and that is the single thing that reads as "web page" no
     * matter how the contents are styled. This is the rule most likely to creep
     * back one screen at a time.
     */
    public function test_no_console_view_reintroduces_a_centred_max_width_column(): void
    {
        $offenders = [];

        foreach ($this->files('resources/views/livewire', '.blade.php') as $file) {
            $rel = $this->rel($file);

            // The public catalogue is full-bleed too, but forms and prose are
            // allowed an INTERNAL max-width - what is banned is centring the
            // whole screen. So only mx-auto paired with a max-width counts, and
            // page-level container/max-w-7xl always counts.
            $body = file_get_contents($file);

            $found = [];

            // Page-scale containers are banned outright.
            if (preg_match_all('/\bmax-w-(?:7xl|6xl|5xl|4xl|screen-\w+)\b/', $body, $m)) {
                $found = array_merge($found, array_unique($m[0]));
            }
            if (preg_match('/\bcontainer\b\s+[^"\']*\bmx-auto\b/', $body)) {
                $found[] = 'container mx-auto';
            }

            /*
             * `mx-auto` is only a defect when it centres a LAYOUT column. A
             * centred paragraph inside a card - `mx-auto max-w-[52ch]` on empty
             * state prose - is a reading measure, not a page container, and
             * banning it would be the rule misfiring rather than working.
             * So: ch-based widths are allowed, px/rem/named scales are not.
             */
            if (preg_match_all('/\bmx-auto\b[^"\']*?\bmax-w-(?:\[\d+(?:px|rem)\]|xs|sm|md|lg|xl|\dxl)\b/', $body, $m)) {
                $found = array_merge($found, array_unique($m[0]));
            }

            if ($found !== []) {
                $offenders[] = $rel.': '.implode(', ', $found);
            }
        }

        $this->assertSame([], $offenders,
            'Console views centre their content in a max-width column. The console fills the '
            ."viewport; forms may take an internal max-width but must stay left-aligned:\n"
            .implode("\n", $offenders));
    }
}
