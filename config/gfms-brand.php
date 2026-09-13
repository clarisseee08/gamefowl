<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GFMS brand tokens — the single source of truth for colour
|--------------------------------------------------------------------------
|
| Plain hex strings, deliberately. This file exists because the application
| and its PDF reports cannot share a stylesheet: dompdf is a CSS 2.1 engine
| and cannot parse a custom property, oklch(), flexbox or grid. So the PDF
| templates read their colours from HERE, and `resources/css/app.css` mirrors
| the same values into its @theme block.
|
| NAMING: shadcn-style semantic pairs (background/foreground, card/
| card-foreground, muted/muted-foreground). Adopted for two reasons that are
| both defensible at a panel: the vocabulary is widely understood, and it makes
| a dark theme a second :root block rather than a second set of components —
| every surface already declares the text colour that belongs on it.
|
| tests/Unit/BrandTokensAreMirroredTest.php asserts every hex below appears
| verbatim in app.css, that Console text clears 7:1 on every surface, and that
| every semantic pair clears 4.5:1 — so screen and print cannot silently drift
| apart, and no pairing can quietly become illegible.
|
| THE FOUR CLIENT COLOURS. The farm specified #8B2626, #EF6905, #F1E5A1 and
| #486C2F. Each is placed by what it can actually carry, measured rather than
| assumed:
|
|   #486C2F  green   -> brand chrome (sidebar, mark, rail, PDF chrome).
|   #8B2626  red     -> primary, the interactive colour. 8.74:1 on card, so it
|                       clears the Console 7:1 floor as link text. The green,
|                       at 6.07:1, does not — which is why the roles sit this
|                       way round rather than the other.
|   #F1E5A1  cream   -> warning_bg, the callout ground. It cannot be a general
|                       surface: secondary ink lands at 6.01:1 on it, under the
|                       7:1 floor.
|   #EF6905  orange  -> accent. FILL ONLY, never text: 3.14:1 on white, below
|                       even the 4.5 AA floor. It carries dark ink at 5.51:1,
|                       which is the only way it is used.
|
*/

return [

    /*
     * SURFACES. Tinted neutrals — never pure white for the page, never pure
     * black for text. A pure #FFF page under a #FFF card gives you no
     * elevation to work with, which is precisely what made the previous
     * direction read as a printed document rather than software.
     *
     * These stay neutral. The four client colours contain no neutral, and
     * painting the page cream would drop secondary text to 6.01:1 — below the
     * Console floor the farm's own outdoor use requires.
     */
    'background' => '#FBFBFA',   // the page
    'card' => '#FFFFFF',         // raised surfaces
    'muted' => '#F4F5F3',        // wells, table headers, inactive
    'popover' => '#FFFFFF',
    'border' => '#E4E6E2',
    'input' => '#DFE2DD',

    /*
     * TEXT. Each surface names the foreground that belongs on it, so a
     * component never has to guess and a theme swap never orphans a colour.
     */
    'foreground' => '#161C19',

    /*
     * The Console 7:1 floor. The staff screens are used outdoors in Philippine
     * daylight, where 5:1 secondary text greys out. #4E5550 measures 7.40 on
     * background, 7.67 on card, 7.01 on muted.
     */
    'muted_foreground' => '#4E5550',

    'card_foreground' => '#161C19',
    'popover_foreground' => '#161C19',

    /*
     * PRIMARY — the farm's deep red. THE INTERACTIVE COLOUR: buttons, links,
     * active nav, focus rings. A full scale rather than a single accent;
     * -50/-100 are fills for active nav and selected rows, -600/-700 are hover
     * and press.
     *
     * Red carries the interactive role rather than the green because of one
     * measurement: #8B2626 is 8.74:1 on card and #486C2F is 6.07:1. Link text
     * has to clear the Console 7:1 floor, and only one of the two does.
     */
    'primary_50' => '#FAF0EF',
    'primary_100' => '#F1DAD8',
    'primary_200' => '#E0B4B0',
    'primary_400' => '#B5453F',
    'primary' => '#8B2626',
    'primary_600' => '#74201F',
    'primary_700' => '#5C1A19',
    'primary_900' => '#331010',
    'primary_foreground' => '#FFFFFF',   // 8.74:1 on primary

    /*
     * BRAND — the farm's green. CHROME ONLY.
     *
     * This is the identity colour: the sidebar surface, the mark, the top rail,
     * the auth panel, PDF chrome. It is never used on a button, a link, a status
     * pill, or anything else interactive or semantic — the guard test
     * DesignSystemGuardTest::test_brand_red_is_never_used_on_an_interactive_element
     * enforces that separation.
     */
    'brand' => '#486C2F',
    'brand_deep' => '#243619',
    'brand_deeper' => '#1A2711',
    'brand_foreground' => '#EDF3EA',   // 11.51:1 on brand_deep
    'brand_muted_fg' => '#BFCFBB',     //  7.95:1 on brand_deep

    /*
     * ACCENT — the farm's orange. FILL ONLY, and that is a measurement rather
     * than a stylistic preference.
     *
     * #EF6905 is 3.14:1 on white. That is below the 4.5 AA floor, so it cannot
     * be a link, a label, a status word or any other text, at any size. It
     * carries dark ink at 5.51:1, so it is used as a ground with
     * accent_foreground on it, and as a chart or marker fill.
     *
     * It is also 1.16:1 against the ember band (#E8552E) — very nearly the same
     * colour. So it is kept away from anything sitting near a bloodline tag,
     * where the two would read as the same signal.
     */
    'accent' => '#EF6905',
    'accent_foreground' => '#161C19',   // 5.51:1 on accent

    /*
     * SEMANTIC. Full foreground/background pairs, not single hues. Every pair
     * is verified >= 4.5:1 by BrandTokensAreMirroredTest.
     */
    'success' => '#1F7A4D',      'success_bg' => '#E8F4EE',   // 4.71:1

    /*
     * The cream is the warning ground. The previous warning ink (#906308)
     * measured 4.14:1 on it — just under the floor — so it was taken down to
     * #755006, which reads 5.66:1 on the cream and 7.23:1 on white.
     */
    'warning' => '#755006',      'warning_bg' => '#F1E5A1',   // 5.66:1

    /*
     * DESTRUCTIVE had to move. .btn-primary and .btn-danger are both solid
     * fills carrying white text, so with primary now a deep red the old
     * #B3261E sat 1.34:1 away from it — Save and Delete would have been the
     * same button. #D32F2F is 1.76:1 from primary: the same danger convention,
     * clearly brighter. Its ground was lightened to #FEF5F4 so the alert badge
     * still clears 4.5:1.
     */
    'destructive' => '#D32F2F',  'destructive_bg' => '#FEF5F4',  // 4.64:1
    'info' => '#3C4A8A',         'info_bg' => '#ECEEF8',         // 7.15:1

    'destructive_foreground' => '#FFFFFF',   // 4.98:1 on destructive

    /*
     * THE BAND PALETTE — the identity, and the one thing carried unchanged
     * through every direction change.
     *
     * A gamefowl's identity is not a row id; it is a numbered anodised ring on
     * its leg. Colour in this system means bloodline and nothing else — status
     * uses the semantic pairs above, never these.
     */
    'bands' => [
        'ember' => '#E8552E',
        'amber' => '#F2A413',
        'jade' => '#1F9E6B',
        'cobalt' => '#1D5FD0',
        'plum' => '#8E44AD',
        'rose' => '#D6336C',
    ],

    /*
     * The band tag picks its own text colour.
     *
     * Three of the six (ember 3.64, jade 3.41, amber a hopeless 2.08) cannot
     * carry white text. So the hexes stay exactly as specified and the
     * FOREGROUND is resolved per band: white where it clears 4.5:1, deep ink
     * where it does not. The rule extends automatically to any colour the
     * deterministic hash produces for an unanticipated bloodline.
     * App\Support\BandTag::foreground() computes it.
     */
    'band_foreground_light' => '#FFFFFF',
    'band_foreground_dark' => '#10201B',

    /*
     * Curated bloodline -> band slot, so the farm's real stock is stable and
     * recognisable across every screen and every session.
     *
     * IMPORTANT: `bloodline` is a free-text varchar(120), NOT an enum. A
     * keeper can type anything. Anything not listed here falls back to a
     * deterministic hash (see App\Support\BandTag), so a newly invented
     * bloodline still renders correctly instead of appearing unstyled.
     *
     * Keys are lowercased and trimmed before lookup.
     */
    'bloodlines' => [
        'sweater' => ['slot' => 'cobalt', 'code' => 'SW'],
        'hatch' => ['slot' => 'ember', 'code' => 'HA'],
        'kelso' => ['slot' => 'jade', 'code' => 'KE'],
        'roundhead' => ['slot' => 'amber', 'code' => 'RH'],
        'grey' => ['slot' => 'plum', 'code' => 'GR'],
        'claret' => ['slot' => 'rose', 'code' => 'CL'],
    ],

    /*
     * PRINT. dompdf gets colour, rules and type from the same source, but not
     * elevation or tinted card grounds — a shadow costs ink and renders as a
     * grey smear on a mono office printer. Print-adapted, not print-different.
     */
    'print' => [
        'rule' => '#E4E6E2',
        'rule_strong' => '#C7CBC5',
        'zebra' => '#F7F8F6',
    ],

    /*
     * DARK. The surfaces and the ink, and nothing else.
     *
     * WHAT IS NOT HERE IS THE POINT. The bands, the brand green, primary and
     * the five semantic pairs are all absent, because they do not change: a
     * Sweater bird is cobalt under any theme, and colour in this system means
     * bloodline. A dark theme that re-tinted the bands would be inventing a
     * second meaning for the one channel that already has one.
     *
     * The band tag needs no adjustment either. It is a FILLED capsule, so its
     * legibility is its own text against its own colour - which
     * App\Support\BandTag::foreground already resolves per band - not the tag
     * against the page.
     *
     * THE SAME 7:1 CONSOLE FLOOR APPLIES, and these values were measured
     * against it rather than picked by eye. The binding pair is
     * muted_foreground on muted, which is the narrowest gap in either theme:
     *
     *   foreground       on background  17.85:1
     *   foreground       on card        16.23:1
     *   foreground       on muted       13.19:1
     *   muted_foreground on background  10.24:1
     *   muted_foreground on card         9.32:1
     *   muted_foreground on muted        7.57:1   <- the binding constraint
     *
     * An earlier muted of #2E3531 put that last pair at 6.77:1. It reads as
     * fine and it is below the floor, which is exactly why the test measures
     * rather than trusting the eye.
     *
     * PDFs DO NOT USE THIS. dompdf reads the light values above directly, and
     * paper has no dark mode.
     */
    'dark' => [
        'background' => '#0D110F',   // the page
        'card' => '#161C19',         // raised surfaces
        'muted' => '#272D2A',        // wells, table headers, inactive
        'popover' => '#1B2220',
        'border' => '#2E3531',
        'input' => '#39413C',

        'foreground' => '#F7F8F6',
        'muted_foreground' => '#B9C0BA',
        'card_foreground' => '#F7F8F6',
        'popover_foreground' => '#F7F8F6',

        /*
         * PRIMARY INVERTS, and leaving it out was a real bug rather than a
         * near miss.
         *
         * primary is the interactive colour - link text, active nav, the focus
         * ring - and #8B2626 measures 1.98:1 against the dark card. Not
         * "slightly low": a link nobody can see. It shipped that way in the
         * first dark build and looked like a styling nicety until measured.
         *
         * primary_200 is the same hue, four steps lighter, and it is doing two
         * jobs at once:
         *
         *   as link text on the dark card    9.33:1
         *   as a button fill, with dark ink  9.33:1
         *
         * Which is why primary_foreground inverts with it. A light fill
         * keeping white text would have measured 1.85:1 - the same bug moved
         * from the link to the button.
         */
        'primary' => '#E0B4B0',
        'primary_foreground' => '#161C19',
    ],

];
