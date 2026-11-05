<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Icon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Every icon a view asks for must exist.
 *
 * <x-ui.icon> renders an empty box for a name it does not know - the kind of
 * failure that ships, because the page still renders and nothing throws. So
 * every literal name in the views is checked against the set here.
 */
final class IconNamesTest extends TestCase
{
    public function test_every_icon_named_in_a_view_exists(): void
    {
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $source = $file->getContents();

            preg_match_all('/<x-ui\.icon\b[^>]*?\sname="([a-z0-9-]+)"/', $source, $literal);
            preg_match_all('/<x-ui\.[a-z.-]+\b[^>]*?\s(?:icon|suffix-icon)="([a-z0-9-]+)"/', $source, $props);

            foreach (array_merge($literal[1], $props[1]) as $name) {
                if (! Icon::exists($name)) {
                    $missing[] = $file->getRelativePathname().': '.$name;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'A view asks for an icon the set does not have.');
    }

    public function test_every_glyph_is_drawable_svg_markup(): void
    {
        foreach (Icon::PATHS as $name => $markup) {
            $this->assertMatchesRegularExpression('/^(<(path|circle|line|polyline|rect)\b[^<>]*\/>)+$/', $markup, "The {$name} glyph is not plain SVG shapes.");
        }
    }

    public function test_an_unknown_name_renders_nothing_rather_than_failing(): void
    {
        $this->assertFalse(Icon::exists('not-a-glyph'));
        $this->assertSame('', Icon::paths('not-a-glyph'));
    }
}
