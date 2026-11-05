<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * The component gallery at /design.
 *
 * Three jobs, in order of who it serves:
 *  1. It is how I check my own work at a glance - every component in every
 *     state on one page, so a broken or unstyled variant is obvious instead of
 *     hiding on a screen nobody opened.
 *  2. It is how parallel work stays consistent - one shared reference rather
 *     than five interpretations of the same brief.
 *  3. It is the defense artifact. "Walk us through your interface design
 *     decisions" is answerable by scrolling this page, with the measured
 *     contrast ratios printed beside the swatches.
 *
 * Gated to internal users: it exposes no data, but a public styleguide on a
 * thesis system is noise a panel does not need to see.
 */
class DesignGalleryController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isInternal() ?? false, 403);

        $brand = config('gfms-brand');

        // The field wrappers read their errors from the shared bag, exactly as
        // they do on a form after validation, so the gallery's error states
        // are real renders of that path rather than hand-written copies.
        ViewFactory::share('errors', (new ViewErrorBag)->put('default', new MessageBag([
            'gallery_weight' => 'Weight cannot be a negative number.',
            'gallery_sex' => "Choose the bird's sex.",
            'gallery_notes' => 'Keep notes under 500 characters.',
        ])));

        return view('design.gallery', [
            'brand' => $brand,
            // Ratios are computed, not typed in. A styleguide that asserts its
            // own accessibility without measuring it is worth nothing.
            'inkPairs' => [
                ['foreground', $brand['foreground'], 'background', $brand['background']],
                ['muted_foreground', $brand['muted_foreground'], 'background', $brand['background']],
                ['muted_foreground', $brand['muted_foreground'], 'muted', $brand['muted']],
                ['primary', $brand['primary'], 'card', $brand['card']],
            ],
            'statusPairs' => collect(['success', 'warning', 'destructive', 'info'])
                ->map(fn (string $k) => [$k, $brand[$k], $k.'_bg', $brand[$k.'_bg']])
                ->all(),
        ]);
    }

    /** WCAG relative-luminance contrast ratio. */
    public static function ratio(string $a, string $b): float
    {
        $lum = function (string $hex): float {
            $c = array_map(fn (int $i) => hexdec(substr($hex, $i, 2)) / 255, [1, 3, 5]);
            $c = array_map(
                fn (float $v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
                $c
            );

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };

        $x = $lum($a);
        $y = $lum($b);

        return round((max($x, $y) + 0.05) / (min($x, $y) + 0.05), 2);
    }
}
