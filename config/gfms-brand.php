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
| tests/Unit/BrandTokensAreMirroredTest.php asserts every hex below appears
| verbatim in app.css, so screen and print cannot silently drift apart. That
| test is the defensible answer to "how do you keep the reports looking like
| the app?".
|
| Full rationale, contrast measurements and usage rules: docs/design-brief.md
|
*/

return [

    /*
     * Ground and ink. The reference is a field ledger - dark ink on pale
     * paper - because the Console is used outdoors in Philippine daylight and
     * contrast is a legibility requirement, not a style preference.
     */
    'paper' => '#FAF9F7',
    'card' => '#FFFFFF',
    'sunk' => '#F1EFEA',

    'ink' => '#1A1917',        // 16.7:1 on paper
    'ink_muted' => '#55534D',  //  7.31:1 on paper - passes the Console 7:1 floor
    'ink_faint' => '#726F66',  //  4.77:1 on paper - Catalog only, never Console body

    'rule' => '#E3E0DA',
    'rule_strong' => '#CFCBC2',

    /*
     * The one interactive colour. Blue-black ledger ink, not a bright UI blue:
     * it sits at 12.45:1 on paper so it survives glare, and it is chromatically
     * distant from all six band colours so "clickable" never reads as a
     * bloodline.
     */
    'action' => '#16324F',
    'action_hover' => '#1E4468',
    'action_wash' => '#E9EEF4',

    /*
     * Semantic status. Separate from `action` so a red badge never reads as a
     * link, and separate from the band palette so status never reads as
     * bloodline. Every pair below is >= 4.5:1 text-on-wash.
     */
    'ok' => '#1B6B44',        'ok_wash' => '#E6F1EB',      // 5.61:1
    'warn' => '#8A5A12',      'warn_wash' => '#F8EFDF',    // 5.18:1
    'alert' => '#A32219',     'alert_wash' => '#F7E8E7',   // 6.30:1
    'note' => '#2A4E7A',      'note_wash' => '#E9EEF4',    // 7.29:1
    'quiet' => '#5E5B55',     'quiet_wash' => '#EFEDE9',   // 5.79:1

    /*
     * The band palette - the ONLY chroma in the system.
     *
     * These are the anodised aluminium colours that poultry leg bands are
     * actually sold in. Not a decorative palette; the physical object.
     * All six carry white text at >= 4.5:1, which is what keeps the tag
     * legible whichever bloodline it lands on.
     */
    'bands' => [
        'crimson' => '#B3202C',   // 6.65:1 with white
        'cobalt' => '#1B4F9C',    // 7.94:1
        'forest' => '#1E6B45',    // 6.47:1
        'amber' => '#9A5B08',     // 5.42:1
        'plum' => '#6A3080',      // 8.95:1
        'slate' => '#41525E',     // 8.10:1
    ],

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
        'hatch' => ['slot' => 'crimson', 'code' => 'HA'],
        'kelso' => ['slot' => 'forest', 'code' => 'KE'],
        'roundhead' => ['slot' => 'amber', 'code' => 'RH'],
        'grey' => ['slot' => 'slate', 'code' => 'GR'],
        'claret' => ['slot' => 'plum', 'code' => 'CL'],
    ],

];
