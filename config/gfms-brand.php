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
| THE FIVE-STEP FOREST PALETTE. #0F2A1D, #375534, #6B9071, #AEC3B0 and
| #E3EED4 replace the earlier red/orange/cream/green set. Each is placed by
| what it can actually carry, measured rather than assumed:
|
|   #0F2A1D  forest  -> ink (foreground), brand_deep chrome, primary_900.
|                       15.34:1 on card.
|   #375534  pine    -> primary, the interactive colour, and brand. 8.36:1 on
|                       card and 7.61:1 on the page, so it clears the Console
|                       7:1 floor as link text. White on it is 8.36:1.
|   #6B9071  sage    -> primary_400. FILL ONLY, never text: 3.58:1 on white,
|                       4.27:1 under forest ink — under 4.5 either way.
|   #AEC3B0  mist    -> primary_200, accent, input border, and the light
|                       primary of the dark theme. Carries forest ink at 8.20:1.
|   #E3EED4  celery  -> muted (wells, sidebar, table headers), primary_100,
|                       brand_foreground. Secondary ink holds 7.41:1 on it.
|
| The cream and the red survive only as semantic grounds: warning_bg and
| destructive. They mean status, not brand.
|
*/

return [

    /*
     * SURFACES. Tinted with the palette's lightest step — never pure white for
     * the page, never pure black for text. A pure #FFF page under a #FFF card
     * gives you no elevation to work with.
     *
     * The page is a lighter cut of #E3EED4 rather than #E3EED4 itself: on the
     * full celery, primary link text measures 6.95:1 — a hair under the
     * Console floor. On #F1F6EA it is 7.61:1. The celery is kept, exact, as
     * muted, where the sidebar and table headers carry ink rather than links.
     */
    'background' => '#F1F6EA',   // the page
    'card' => '#FFFFFF',         // raised surfaces
    'muted' => '#E3EED4',        // wells, table headers, sidebar, inactive
    'popover' => '#FFFFFF',
    'border' => '#D3E0C6',
    'input' => '#AEC3B0',

    /*
     * TEXT. Each surface names the foreground that belongs on it, so a
     * component never has to guess and a theme swap never orphans a colour.
     */
    'foreground' => '#0F2A1D',

    /*
     * The Console 7:1 floor. The staff screens are used outdoors in Philippine
     * daylight, where 5:1 secondary text greys out. #34503A measures 8.12 on
     * background, 8.92 on card, 7.41 on muted.
     */
    'muted_foreground' => '#34503A',

    'card_foreground' => '#0F2A1D',
    'popover_foreground' => '#0F2A1D',

    /*
     * PRIMARY — pine, #375534. THE INTERACTIVE COLOUR: buttons, links, active
     * nav, focus rings. A full scale rather than a single accent; -50/-100 are
     * fills for active nav and selected rows, -600/-700 are hover and press.
     * The scale is the five palette steps with the gaps between them filled.
     */
    'primary_50' => '#EEF4E6',
    'primary_100' => '#E3EED4',
    'primary_200' => '#AEC3B0',
    'primary_400' => '#6B9071',
    'primary' => '#375534',
    'primary_600' => '#2C4A2D',
    'primary_700' => '#1F3D25',
    'primary_900' => '#0F2A1D',
    'primary_foreground' => '#FFFFFF',   // 8.36:1 on primary

    /*
     * BRAND — the forest green. CHROME ONLY.
     *
     * This is the identity colour: the sidebar surface, the mark, the top rail,
     * the auth panel, PDF chrome. It is never used on a button, a link, a status
     * pill, or anything else interactive or semantic — the guard test
     * DesignSystemGuardTest::test_brand_red_is_never_used_on_an_interactive_element
     * enforces that separation.
     */
    'brand' => '#375534',
    'brand_deep' => '#0F2A1D',
    'brand_deeper' => '#0A1E14',
    'brand_foreground' => '#E3EED4',   // 12.74:1 on brand_deep
    'brand_muted_fg' => '#AEC3B0',     //  8.20:1 on brand_deep

    /*
     * ACCENT — mist, #AEC3B0. FILL ONLY: 1.87:1 on white, so it is never text.
     * It carries forest ink at 8.20:1, so it is used as a ground with
     * accent_foreground on it, and as a chart or marker fill.
     */
    'accent' => '#AEC3B0',
    'accent_foreground' => '#0F2A1D',   // 8.20:1 on accent

    /*
     * SEMANTIC. Full foreground/background pairs, not single hues. Every pair
     * is verified >= 4.5:1 by BrandTokensAreMirroredTest.
     */
    'success' => '#1F7A4D',      'success_bg' => '#E8F4EE',   // 4.71:1

    /*
     * The cream is the warning ground — kept from the earlier palette because
     * amber-on-cream reads as "attention" and no green can.
     *
     * The previous warning ink (#906308)
     * measured 4.14:1 on it — just under the floor — so it was taken down to
     * #755006, which reads 5.66:1 on the cream and 7.23:1 on white.
     */
    'warning' => '#755006',      'warning_bg' => '#F1E5A1',   // 5.66:1

    /*
     * DESTRUCTIVE. .btn-primary and .btn-danger are both solid fills carrying
     * white text. With primary green, Save and Delete now differ by hue as
     * well as by luminance (1.68:1), so #D32F2F reads as danger at a glance.
     * Its ground is #FEF5F4 so the alert badge still clears 4.5:1.
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
        'rule' => '#DCE6D3',
        'rule_strong' => '#AEC3B0',
        'zebra' => '#F3F7EE',
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
     * The surfaces are the forest palette taken toward black, so the dark
     * theme is the same green rather than a neutral grey with green buttons.
     *
     *   foreground       on background  16.37:1
     *   foreground       on card        14.90:1
     *   foreground       on muted       12.47:1
     *   muted_foreground on background   9.82:1
     *   muted_foreground on card         8.94:1
     *   muted_foreground on muted        7.48:1   <- the binding constraint
     *
     * An earlier muted of #2E3531 put that last pair at 6.77:1. It reads as
     * fine and it is below the floor, which is exactly why the test measures
     * rather than trusting the eye.
     *
     * PDFs DO NOT USE THIS. dompdf reads the light values above directly, and
     * paper has no dark mode.
     */
    'dark' => [
        'background' => '#0A1711',   // the page
        'card' => '#11211A',         // raised surfaces
        'muted' => '#1C3027',        // wells, table headers, inactive
        'popover' => '#152820',
        'border' => '#243A2F',
        'input' => '#2F4A3C',

        'foreground' => '#EEF4E6',
        'muted_foreground' => '#AEC3B0',
        'card_foreground' => '#EEF4E6',
        'popover_foreground' => '#EEF4E6',

        /*
         * PRIMARY INVERTS, and leaving it out was a real bug rather than a
         * near miss.
         *
         * primary is the interactive colour - link text, active nav, the focus
         * ring - and pine #375534 measures 1.97:1 against the dark card. Not
         * "slightly low": a link nobody can see.
         *
         * #A9D49B is the same green family lifted and given more chroma than
         * the mist muted_foreground, so a link still reads as a link next to
         * secondary text. It is doing two jobs at once:
         *
         *   as link text on the dark card    10.01:1
         *   as a button fill, with forest    9.19:1
         *
         * Which is why primary_foreground inverts with it. A light fill
         * keeping white text would fail - the same bug moved from the link to
         * the button.
         */
        'primary' => '#A9D49B',
        'primary_foreground' => '#0F2A1D',

        /*
         * THE SEMANTIC PAIRS INVERT TOO, and leaving them out was the same bug
         * as leaving primary out - it just took longer to spot because it is
         * only wrong on four tokens rather than on every link.
         *
         * The light values are ink-on-paper: dark inks on pale tints. Carried
         * unchanged onto a #0A1711 page they become dark-on-dark. The worst was
         * warning, #755006, an olive that measures 2.57:1 on the dark card: the
         * dashboard's vaccination-compliance figure was rendering in a colour
         * nobody could read, on the one screen a keeper opens every morning.
         *
         * So each ink lightens and each ground darkens, and every value here
         * was measured rather than picked - BrandTokensAreMirroredTest re-measures
         * them. The floor is the Console 7:1 on background, card AND muted,
         * because these are dashboard text, not decoration.
         *
         * DESTRUCTIVE IS SEPARATED FROM PRIMARY BY HUE. In dark both are light
         * fills under dark ink, so luminance cannot tell Delete from Save; coral
         * #FFA88F against leaf #A9D49B can.
         */
        'success' => '#6FD39B',          //  9.13:1 on card, 7.64:1 on muted
        'success_bg' => '#182B21',       //  8.15:1 under its own ink
        'warning' => '#E9C46A',          // 10.00:1 on card, 8.37:1 on muted
        'warning_bg' => '#2E2716',       //  8.86:1 under its own ink
        'destructive' => '#FFA88F',      //  8.95:1 on card, 7.49:1 on muted
        'destructive_bg' => '#301B1A',   //  8.67:1 under its own ink
        'destructive_foreground' => '#0F2A1D',  // on the destructive fill
        'info' => '#AEBBEF',             //  8.88:1 on card, 7.43:1 on muted
        'info_bg' => '#1E2336',          //  8.27:1 under its own ink
    ],

];
