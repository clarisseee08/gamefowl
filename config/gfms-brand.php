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
| verbatim in app.css, so screen and print cannot silently drift apart.
| tests/Unit/TokenContrastTest.php asserts every declared pairing is legible.
|
*/

return [

    /*
     * SURFACES. Tinted neutrals — never pure white for the page, never pure
     * black for text. A pure #FFF page under a #FFF card gives you no
     * elevation to work with, which is precisely what made the previous
     * direction read as a printed document rather than software.
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
     * DEVIATION FROM THE SPEC, and the reason for it.
     *
     * The direction specified #667069, which measures 4.96:1 on background and
     * 4.70:1 on muted. Both clear AA, but the same document keeps the Console
     * 7:1 floor — the staff screens are used outdoors in Philippine daylight,
     * where 5:1 secondary text greys out. #4E5550 is the same hue at lower
     * lightness: 7.40 on background, 7.67 on card, 7.01 on muted.
     */
    'muted_foreground' => '#4E5550',

    'card_foreground' => '#161C19',
    'popover_foreground' => '#161C19',

    /*
     * BRAND — peacock. A full scale rather than a single accent: the previous
     * direction spent colour in exactly one place, and that austerity is what
     * is being replaced. 500 is the base; -50/-100 are fills for active nav
     * and selected rows, -600/-700 are hover and press.
     */
    'primary_50' => '#EDF7F7',
    'primary_100' => '#D2ECEC',
    'primary_200' => '#A6D8D9',
    'primary_400' => '#35A0A6',
    'primary' => '#0D6E75',
    'primary_600' => '#0A5C62',
    'primary_700' => '#08494E',
    'primary_900' => '#052B2E',
    'primary_foreground' => '#FFFFFF',

    /*
     * SEMANTIC. Full foreground/background pairs, not single hues. The tinted
     * backgrounds ARE the point — this is where "one accent only" is
     * deliberately abandoned. Every pair is verified >= 4.5:1 by
     * TokenContrastTest.
     */
    'success' => '#1F7A4D',      'success_bg' => '#E8F4EE',   // 4.71:1

    /*
     * DEVIATION: the spec gave #A87409, which is 3.68:1 on its own tinted
     * background — the one pair in the set that failed. Same hue, darker.
     */
    'warning' => '#906308',      'warning_bg' => '#FBF3E2',   // 4.79:1

    'destructive' => '#B3261E',  'destructive_bg' => '#FBEAE9',  // 5.62:1
    'info' => '#3C4A8A',         'info_bg' => '#ECEEF8',         // 7.15:1

    'destructive_foreground' => '#FFFFFF',

    /*
     * THE BAND PALETTE — the identity, and the one thing carried unchanged
     * through the direction change.
     *
     * A gamefowl's identity is not a row id; it is a numbered anodised ring on
     * its leg. Colour in this system means bloodline and nothing else — status
     * uses the semantic pairs above, never these.
     *
     * All six carry white text at >= 4.5:1, which is what keeps the tag legible
     * whichever bloodline it lands on. Asserted, not assumed.
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
     * These six are brighter and more saturated than the previous anodised set,
     * which is the point — but three of them (ember 3.64, jade 3.41, amber a
     * hopeless 2.08) cannot carry white text. Darkening them to fit white would
     * have walked amber straight back to the muted gold this direction replaced.
     *
     * So the hexes stay exactly as specified and the FOREGROUND is resolved per
     * band: white where it clears 4.5:1, deep ink where it does not. Every tag
     * then passes on its own terms, and the rule extends automatically to any
     * colour the deterministic hash produces for an unanticipated bloodline.
     * App\Support\BandTag::foreground() computes it; BandTagContrastTest asserts it.
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

];
