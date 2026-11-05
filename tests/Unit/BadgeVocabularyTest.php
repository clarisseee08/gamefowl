<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\HealthRecordType;
use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Five PHP enums return CSS class strings from badgeClasses(), consumed at ~22
 * call sites across the Blade views. If the CSS vocabulary is renamed without
 * the enums following - or vice versa - the badges render completely unstyled
 * and NOTHING in the test suite notices, because every existing assertion is on
 * copy and data rather than on class names.
 *
 * That is an invisible failure mode. This converts it into a red test.
 *
 * The badges are daisyUI now (`d-badge-success`, …), so "defined" means present
 * in the COMPILED bundle rather than in app.css: daisyUI only emits a class
 * Tailwind found in a scanned source, and a class it never emitted renders
 * unstyled exactly as a misspelt one used to.
 */
final class BadgeVocabularyTest extends TestCase
{
    private function appCss(): string
    {
        return file_get_contents(__DIR__.'/../../resources/css/app.css');
    }

    private function compiledCss(): string
    {
        $bundles = glob(__DIR__.'/../../public/build/assets/*.css') ?: [];

        if ($bundles === []) {
            $this->markTestSkipped('No compiled bundle. Run `npm run build` first.');
        }

        return implode('', array_map('file_get_contents', $bundles));
    }

    /** Emitted means a selector for it exists, bare or behind a variant (`.lg\:d-drawer-open`). */
    private function bundleDefines(string $css, string $class): bool
    {
        return preg_match('/[.:]'.preg_quote($class, '/').'(?![\w-])/', $css) === 1;
    }

    public static function enums(): array
    {
        return [
            'broodcock status' => [BroodcockStatus::class],
            'broodcock class' => [BroodcockClass::class],
            'health record type' => [HealthRecordType::class],
            'performance event type' => [PerformanceEventType::class],
            'performance result' => [PerformanceResult::class],
        ];
    }

    #[DataProvider('enums')]
    public function test_every_badge_class_an_enum_emits_is_defined_in_the_stylesheet(string $enum): void
    {
        $css = $this->compiledCss();

        foreach ($enum::cases() as $case) {
            foreach (preg_split('/\s+/', trim($case->badgeClasses())) as $class) {
                if ($class === '') {
                    continue;
                }

                $this->assertTrue($this->bundleDefines($css, $class),
                    "{$enum}::{$case->name} emits '{$class}', which the compiled bundle does not define. "
                    .'The enum and the stylesheet have drifted apart and this badge renders unstyled.');
            }
        }
    }

    /**
     * Band colour is an identity channel. Borrowing it for status would destroy
     * the encoding that makes a bloodline scannable in a table.
     */
    #[DataProvider('enums')]
    public function test_no_enum_borrows_a_band_colour_for_status(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $this->assertStringNotContainsString('band-', $case->badgeClasses(),
                "{$enum}::{$case->name} uses a band colour. Band colour means bloodline, never status.");
        }
    }

    /**
     * The enum test above only sees classes the ENUMS emit. It cannot see a
     * component class typed by hand directly into a Blade view - which is
     * exactly how `badge-quiet` (a class that has never existed; the real one is
     * `badge-neutral`) shipped into two views and rendered as an unstyled pill
     * with the whole suite green.
     *
     * Tailwind gives no warning for this: an undefined utility simply produces
     * no CSS. So the only thing that can catch it is a test that reads the views.
     */
    public function test_every_component_class_used_in_a_view_actually_exists(): void
    {
        $css = $this->appCss();
        $compiled = $this->compiledCss();
        $views = $this->bladeFiles();
        $unknown = [];

        // Only the closed vocabularies. Tailwind's own utilities are open-ended
        // and generated on demand, so they cannot be checked this way.
        //
        // The (?<![\w-]) guard stops `d-badge-soft` being read as a retired GFMS
        // class `badge-soft`. The retired prefixes stay listed, so a leftover
        // `btn-primary` fails here now that app.css no longer defines it.
        $prefixes = ['badge-', 'btn-', 'input-', 'band-tag', 'ped-', 'table-hairline', 'datum'];

        foreach ($views as $file) {
            $body = file_get_contents($file);

            // daisyUI is prefixed `d-`; every such class must have been emitted.
            preg_match_all('/(?<![\w-])(d-[a-z][a-z0-9-]*)\b/', $body, $daisy);

            foreach (array_unique($daisy[1]) as $class) {
                if (! $this->bundleDefines($compiled, $class)) {
                    $unknown[] = basename(dirname($file)).'/'.basename($file).": {$class}";
                }
            }

            foreach ($prefixes as $prefix) {
                preg_match_all('/(?<![\w-])('.preg_quote($prefix, '/').'[a-z0-9-]*)\b/i', $body, $m);

                foreach (array_unique($m[1]) as $class) {
                    // Blade interpolation such as {{ $enum->badgeClasses() }} is
                    // covered by the enum test above and cannot be read statically.
                    if (str_contains($css, ".{$class}") || str_contains($css, "@utility {$class}")) {
                        continue;
                    }
                    $unknown[] = basename(dirname($file)).'/'.basename($file).": {$class}";
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unknown)),
            'Blade views reference component classes that neither app.css nor the compiled bundle define. '
            ."Tailwind emits no CSS and no warning for these, so they render unstyled:\n"
            .implode("\n", array_unique($unknown)));
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $root = __DIR__.'/../../resources/views';
        $files = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                // PDF templates are deliberately outside the system: dompdf is a
                // CSS 2.1 engine and they carry their own inline styles.
                if (! str_contains(str_replace('\\', '/', $file->getPathname()), '/reports/pdf/')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
