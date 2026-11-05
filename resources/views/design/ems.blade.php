{{--
    EMS COMPONENTS - every wrapper in resources/views/components/ui, in every
    state. Part B's checklist asks for each variant rendered once in both
    themes; this is that page. Switch the theme and look again.
--}}
@php
    $sectionTitle = 'text-sm font-semibold text-foreground';
    $sectionNote = 'mt-1 max-w-[68ch] text-xs text-muted-foreground';
    $caption = 'text-[11px] font-medium text-muted-foreground';
@endphp

<section class="space-y-10" aria-labelledby="ems-heading">
    <div>
        <h2 id="ems-heading" class="text-base font-bold text-foreground">EMS components</h2>
        <p class="{{ $sectionNote }}">
            The wrappers every screen is moving to, in every state. Compact from the lg breakpoint up,
            touch-sized below it.
        </p>
    </div>

    {{-- BUTTONS --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">Button</h3>

        <div class="space-y-2">
            <p class="{{ $caption }}">App size (the screen default)</p>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button><x-ui.icon name="plus" /> Add gamefowl</x-ui.button>
                <x-ui.button variant="destructive"><x-ui.icon name="trash-2" /> Delete</x-ui.button>
                <x-ui.button variant="outline">Export CSV</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="ghost">Cancel</x-ui.button>
                <x-ui.button variant="link">View record</x-ui.button>
                <x-ui.button variant="success"><x-ui.icon name="check" /> Approve</x-ui.button>
                <x-ui.button variant="outline-destructive">Remove</x-ui.button>
                <x-ui.button variant="outline-success">Mark done</x-ui.button>
            </div>
        </div>

        <div class="space-y-2">
            <p class="{{ $caption }}">States</p>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button :loading="true">Saving...</x-ui.button>
                <x-ui.button variant="outline" :loading="true">Processing...</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
                <x-ui.button variant="outline" disabled>Disabled</x-ui.button>
                <x-ui.button size="app-circle" variant="outline" aria-label="Refresh"><x-ui.icon name="refresh-cw" /></x-ui.button>
            </div>
        </div>

        <div class="space-y-2">
            <p class="{{ $caption }}">Base sizes</p>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm" variant="outline">Small</x-ui.button>
                <x-ui.button size="default">Default</x-ui.button>
                <x-ui.button size="lg">Large</x-ui.button>
                <x-ui.button size="icon-sm" variant="ghost" aria-label="Edit"><x-ui.icon name="pencil" /></x-ui.button>
                <x-ui.button size="icon" variant="outline" aria-label="Settings"><x-ui.icon name="settings" /></x-ui.button>
                <x-ui.button size="icon-lg" variant="ghost" aria-label="More actions"><x-ui.icon name="ellipsis-vertical" /></x-ui.button>
            </div>
        </div>
    </div>

    {{-- FIELDS --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">Input, textarea, native select</h3>

        <div class="grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input id="gallery_band" label="Band number" placeholder="e.g. SW-1024"
                        help="Leave empty if the bird has not been banded yet." />
            <x-ui.input id="gallery_search" label="Search" icon="search" placeholder="Search gamefowl..." />
            <x-ui.input id="gallery_name" label="Name" required placeholder="e.g. Tandang" />
            <x-ui.input id="gallery_weight" label="Weight (kg)" value="-2" class="datum" />
            <x-ui.input id="gallery_hatched" label="Hatch date" type="date" />
            <x-ui.input id="gallery_disabled" label="Recorded by" value="Owner" disabled />
            <x-ui.native-select id="gallery_line" label="Bloodline" placeholder="All bloodlines"
                                :options="['sweater' => 'Sweater', 'kelso' => 'Kelso', 'hatch' => 'Hatch']" />
            <x-ui.native-select id="gallery_sex" label="Sex" required placeholder="Choose..."
                                :options="['male' => 'Cock', 'female' => 'Hen']" />
            <x-ui.textarea id="gallery_remarks" label="Remarks" placeholder="Temperament, markings, anything a buyer should know"
                           wrapper-class="sm:col-span-2" />
            <x-ui.textarea id="gallery_notes" label="Notes" wrapper-class="sm:col-span-2">A very long note.</x-ui.textarea>
        </div>
    </div>

    {{-- CHOICE CONTROLS --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">Checkbox, radio, switch, slider</h3>

        <div class="grid max-w-3xl grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="space-y-1">
                <x-ui.checkbox label="For sale" checked />
                <x-ui.checkbox label="Some of these selected" :indeterminate="true" />
                <x-ui.checkbox label="Not chosen" help="A second line that adds a consequence." />
                <x-ui.checkbox label="Disabled" disabled />
            </div>

            <div class="space-y-1" role="radiogroup" aria-label="Sex">
                <x-ui.radio name="gallery_radio" value="cock" label="Cock" checked />
                <x-ui.radio name="gallery_radio" value="hen" label="Hen" />
                <x-ui.radio name="gallery_radio" value="unknown" label="Not known yet" disabled />
            </div>

            <div class="space-y-1">
                <x-ui.switch label="Show in the catalogue" checked />
                <x-ui.switch label="Email me when a vaccination is due" />
                <x-ui.switch label="Disabled" disabled />
            </div>

            <div class="space-y-3">
                <x-ui.slider :value="40" aria-label="Example value" />
                <x-ui.slider :value="75" disabled aria-label="Disabled example" />
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">KPI tile and summary card</h3>

        <x-ui.kpi-row>
            <x-ui.kpi-tile label="Birds on the farm" value="26" icon="user" caption="Across 30 birds on record" />
            <x-ui.kpi-tile label="Fertility" value="75.0%" :delta="4.2" delta-unit=" pts" caption="Eggs set in the last 12 months" />
            <x-ui.kpi-tile label="Deaths this year" value="3" :delta="2" :higher-is-better="false" caption="Against 1 by this time last year" />
            <x-ui.kpi-tile label="Hatch rate" value="81.3%" :delta="0" caption="Same as the month before" />
        </x-ui.kpi-row>

        <x-ui.kpi-row>
            <x-ui.kpi-tile label="Overdue vaccinations" value="4" :alarm="true" icon="triangle-alert"
                           href="#ems-heading" action="Show the overdue vaccinations" caption="Due before today" />
            <x-ui.kpi-tile label="Pedigree gaps" caption="Birds with no parents recorded" />
            <x-ui.kpi-tile label="Loading" :loading="true" />
            <x-ui.kpi-tile label="Matings" value="12" caption="In the selected period" />
        </x-ui.kpi-row>

        <x-ui.kpi-row>
            <x-ui.summary-card title="Birds on the farm" value="26" icon="user" footer="+3 since last month" />
            <x-ui.summary-card title="Deaths this year" value="3" icon="triangle-alert" tone="danger" footer="-1 against last year" />
            <x-ui.summary-card title="Due soon" value="7" icon="calendar" tone="warning" footer="In the next 30 days" />
            <x-ui.summary-card title="Vaccinated" value="92%" icon="circle-check" tone="success" footer="Of birds on the farm" href="#ems-heading" />
        </x-ui.kpi-row>
    </div>

    {{-- CARDS --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">Card</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.card>
                <x-ui.card.header>
                    <x-ui.card.title as="h4">Farm profile</x-ui.card.title>
                    <x-ui.card.description>Shown on the public site and on every report.</x-ui.card.description>
                    <x-ui.card.action>
                        <x-ui.button variant="outline" size="app"><x-ui.icon name="pencil" /> Edit</x-ui.button>
                    </x-ui.card.action>
                </x-ui.card.header>
                <x-ui.card.content>
                    <p class="text-xs text-foreground">A flat card: a border and no shadow. Elevation is for overlays.</p>
                </x-ui.card.content>
                <x-ui.card.footer class="gap-2">
                    <x-ui.button variant="ghost">Cancel</x-ui.button>
                    <x-ui.button>Save</x-ui.button>
                </x-ui.card.footer>
            </x-ui.card>

            <x-ui.card variant="table">
                <div class="flex items-center justify-between gap-2 border-b border-border/40 px-4 py-3">
                    <x-ui.input id="gallery_table_search" icon="search" placeholder="Search records..." aria-label="Search records" wrapper-class="max-w-xs" />
                    <x-ui.button variant="outline"><x-ui.icon name="download" /> Export</x-ui.button>
                </div>
                <div class="px-4 py-6 text-center text-xs text-muted-foreground">The table variant: a toolbar strip and a table, edge to edge.</div>
            </x-ui.card>
        </div>
    </div>

    {{-- SMALL PIECES --}}
    <div class="space-y-4">
        <h3 class="{{ $sectionTitle }}">Skeleton, progress, separator</h3>

        <div class="grid max-w-3xl grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="space-y-2" aria-busy="true">
                <x-ui.skeleton class="h-8 w-48" />
                <x-ui.skeleton class="h-4 w-full" />
                <x-ui.skeleton class="h-4 w-3/4" />
                <x-ui.skeleton class="size-10 rounded-full" />
            </div>
            <div class="space-y-4">
                <x-ui.progress :value="62" label="Pedigree completeness" />
                <x-ui.progress :value="80" tone="warning" label="Storage used" />
                <x-ui.progress :value="100" tone="danger" label="Over the limit" />
                <x-ui.separator />
                <div class="flex h-6 items-center gap-3 text-xs text-muted-foreground">
                    <span>Left</span><x-ui.separator orientation="vertical" /><span>Right</span>
                </div>
            </div>
        </div>
    </div>
</section>
