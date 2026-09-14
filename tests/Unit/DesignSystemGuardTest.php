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
     * Brand red is CHROME ONLY.
     *
     * This system already uses red to mean "dead bird" and "overdue
     * vaccination". If brand red also appeared on a button, a link, or a status
     * pill, a keeper could not tell whether a red thing was branded or urgent —
     * and in a system whose job includes flagging mortality, that ambiguity is a
     * usability defect, not an aesthetic one.
     *
     * So: brand belongs to the sidebar, the mark, the top rail, the auth panel,
     * page-header markers and PDF chrome. Peacock stays the interactive colour.
     */
    public function test_brand_red_is_never_used_on_an_interactive_element(): void
    {
        $offenders = [];

        // Utilities that only ever appear on something you click or focus.
        $interactive = ['btn-primary', 'btn-secondary', 'btn-danger', 'btn-quiet', 'input', 'badge'];

        foreach ($this->files('resources/views', '.blade.php') as $file) {
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($file)) ?? '';

            // Any class attribute that pairs a control class with a brand colour.
            if (preg_match_all('/class="([^"]*)"/', $body, $m)) {
                foreach ($m[1] as $classList) {
                    $hasControl = false;
                    foreach ($interactive as $needle) {
                        if (preg_match('/\b'.preg_quote($needle, '/').'\b/', $classList)) {
                            $hasControl = true;
                            break;
                        }
                    }

                    if ($hasControl && preg_match('/\b(?:bg|text|border|ring)-brand(?:-[a-z-]+)?\b/', $classList, $hit)) {
                        $offenders[] = $this->rel($file).': "'.trim($classList).'" uses '.$hit[0];
                    }
                }
            }
        }

        $this->assertSame([], $offenders,
            'Brand red is on an interactive element. It is chrome only — this app uses red '
            ."for mortality and overdue, and the two must not be confusable:\n"
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

    /**
     * Every Blade view opens and closes the same number of <div>s.
     *
     * This caught two real bugs, both the same authoring slip: a bare `<div>`
     * left above an already-opened one in the catalogue filter row, and another
     * below the broodcock filter grid. Nothing errors - Blade renders happily
     * and the browser silently re-nests everything that follows inside the
     * stray element, which is why it survived. The visible symptom was a
     * catalogue whose card grid inherited the filter panel's layout and whose
     * photo wells collapsed, i.e. "the photos are not showing" with no photo
     * bug anywhere in the stack.
     *
     * A COUNT, NOT A PARSER. It cannot know that a div opened inside @if and
     * closed inside @else is balanced at runtime. That is a real limitation and
     * the reason this is worth stating: no view in this project does that
     * today, so the invariant holds, and the day one legitimately needs to, the
     * honest fix is to restructure the markup rather than to loosen this.
     *
     * BLADE COMMENTS ARE STRIPPED FIRST, and that is not a convenience. A
     * comment explaining WHY a stray <div> inside a <table> gets dropped by the
     * parser was itself counted as an opened div, and the build failed on the
     * sentence rather than on any markup.
     *
     * This project has now been bitten by prose-scanned-as-markup three times:
     * a design document's anti-pattern list compiled the very utilities it
     * forbade, a comment naming a banned duration shipped that duration into
     * production, and this. Every scanner over these files has to decide what
     * a comment is, and the answer is always "not code".
     */
    public function test_every_view_balances_its_divs(): void
    {
        $offenders = [];

        foreach ($this->files('resources/views', '.blade.php') as $file) {
            $body = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file));

            $opened = preg_match_all('/<div(?=[\s>\/])/i', $body);
            $closed = preg_match_all('/<\/div\s*>/i', $body);

            if ($opened !== $closed) {
                $offenders[] = sprintf(
                    '%s: %d opened, %d closed (%+d)',
                    $this->rel($file),
                    $opened,
                    $closed,
                    $opened - $closed
                );
            }
        }

        $this->assertSame([], $offenders,
            'A view opens a <div> it never closes. The browser will re-nest everything after it, '
            .'which breaks the layout silently rather than erroring:
'.implode('
', $offenders));
    }

    /**
     * A visually hidden file input must sit inside a POSITIONED ancestor.
     *
     * Tailwind's `sr-only` is position:absolute. With no positioned ancestor
     * the input is laid out against the document, so clicking the upload zone
     * focuses an element the browser believes is thousands of pixels down the
     * page - and it scrolls the WINDOW to reach it.
     *
     * On this shell that is fatal rather than untidy. The console body is
     * fixed-height and overflow-hidden, with <main> as the only scroll
     * container, so a window scroll drags the entire layout out of view and
     * nothing ever scrolls it back. The page goes blank, the DOM stays
     * perfectly intact, every asset still reports 200, and the console shows no
     * error. It cost a long time to find precisely because nothing looks wrong.
     *
     * Adding `relative` to the wrapping label is the whole fix.
     */
    public function test_a_visually_hidden_file_input_sits_in_a_positioned_wrapper(): void
    {
        $offenders = [];

        foreach ($this->files('resources/views', '.blade.php') as $file) {
            $body = file_get_contents($file);

            if (! str_contains($body, 'type="file"')) {
                continue;
            }

            // Every <label> that contains a file input, with its attributes.
            if (! preg_match_all("/<label\b[^>]*>.*?<input\b[^>]*type=\"file\"[^>]*>/s", $body, $matches)) {
                continue;
            }

            foreach ($matches[0] as $block) {
                $hidden = str_contains($block, 'sr-only');
                $positioned = preg_match("/\bclass=\"[^\"]*\brelative\b/", $block) === 1
                    || str_contains($block, "'relative ")
                    || str_contains($block, ' relative ');

                if ($hidden && ! $positioned) {
                    $offenders[] = $this->rel($file);
                }
            }
        }

        $this->assertSame([], array_unique($offenders),
            'A sr-only file input has no positioned wrapper. Focusing it will scroll the window '
            .'and blank the console layout - add `relative` to the wrapping <label>:
'
            .implode('
', array_unique($offenders)));
    }

    /**
     * An x-data expression must not contain a double quote.
     *
     * THIS ONE COST AN AFTERNOON. x-data is an HTML attribute delimited by
     * double quotes, so the first `"` inside the expression ends the attribute.
     * The browser gets a truncated fragment of JavaScript, Alpine reports
     * "Invalid or unexpected token" against a wall of escaped JSON, and the
     * component silently stops working - while the markup still looks correct
     * in the editor and every server-side test still passes.
     *
     * It happened writing a querySelectorAll('[role="option"]') into the
     * command palette. The fix is the unquoted form, [role=option], which is
     * valid CSS for a bare identifier.
     *
     * The check models what the browser does rather than what the file means:
     * take everything from x-data=" to the very next ", and see whether the
     * braces balance. They only balance if nothing truncated it early.
     */
    public function test_no_alpine_expression_is_cut_short_by_a_double_quote(): void
    {
        $offenders = [];

        foreach ($this->files('resources/views', '.blade.php') as $file) {
            $source = file_get_contents($file);

            if ($source === false) {
                continue;
            }

            // Exactly what the HTML parser sees: up to the NEXT double quote.
            preg_match_all('/x-data="([^"]*)"/s', $source, $matches);

            foreach ($matches[1] as $expression) {
                if (! str_contains($expression, '{')) {
                    continue;   // a bare object name, nothing to balance
                }

                if (substr_count($expression, '{') !== substr_count($expression, '}')) {
                    $offenders[] = $this->rel($file);
                }
            }
        }

        $this->assertSame([], array_unique($offenders),
            'An x-data expression is truncated by a double quote inside it. Alpine will fail '
            .'at runtime with "Invalid or unexpected token" and the component will do nothing. '
            .'Use single quotes, or an unquoted CSS attribute selector:
'
            .implode('
', array_unique($offenders)));
    }
}
