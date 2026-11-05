@php
    $history = $this->history;
    $stats = $this->stats;
    $pageTargets = 'perPage,gotoPage,nextPage,previousPage';

    $th = 'h-10 px-5 text-left text-[13px] font-medium text-muted-foreground lg:h-9 lg:text-xs';
    // Below md each row stacks as a block, so cell padding and rules are reset
    // there and restored from md up.
    $td = 'block border-b-0 px-0 py-0 md:table-cell md:border-b md:border-border md:px-5 md:py-2.5 md:align-top';
@endphp

{{--
    THE REPORTS HUB (EMS).

    The figures from the audit trail first, then the one option every report
    shares (the date range) beside the reports themselves, then the history
    as a table card.

    The date range lives in the browser: picking dates rewrites the download
    links at once, with no round trip. The server reads the range from the
    link when the file is generated, and records it with the report.

    The downloads are plain links, never wire:navigate - a CSV or PDF is a
    file, not a page, and a client-side visit to one quietly does nothing.
--}}
<div x-data="{
        from: @js($from),
        to: @js($to),
        query() {
            const params = new URLSearchParams();
            if (this.from) params.set('from', this.from);
            if (this.to) params.set('to', this.to);
            const query = params.toString();
            return query ? '?' + query : '';
        },
        preset: '',
        iso(date) { return date.toLocaleDateString('en-CA'); },
        choose(key) {
            const today = new Date();
            const start = new Date(today);
            if (key === 'all') { this.from = ''; this.to = ''; }
            if (key === 'month') { start.setDate(1); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'quarter') { start.setMonth(today.getMonth() - 3); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'year') { start.setFullYear(today.getFullYear() - 1); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'calendar') { this.from = today.getFullYear() + '-01-01'; this.to = this.iso(today); }
            this.preset = key;
        },
        get label() {
            const fmt = (value) => new Date(value + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            if (! this.from && ! this.to) return 'all records';
            if (this.from && this.to) return fmt(this.from) + ' to ' + fmt(this.to);
            return this.from ? 'from ' + fmt(this.from) : 'up to ' + fmt(this.to);
        },
     }">
    <x-ui.page-header title="Reports"
                      description="Download any report as a spreadsheet (CSV) or a printable document (PDF). Every report generated is recorded below." />

    <x-ui.kpi-row class="mt-5" role="group" aria-label="Report figures">
        <x-ui.kpi-tile label="Reports generated" icon="clipboard-list" :value="number_format($stats['total'])"
                       caption="Every download, recorded in the audit trail" />
        <x-ui.kpi-tile label="This month" icon="calendar" :value="number_format($stats['thisMonth'])"
                       :caption="'Since '.now()->startOfMonth()->format('j M Y')" />
        <x-ui.kpi-tile label="Most requested" icon="arrow-up-right"
                       :value="$stats['top'] ? Str::headline($stats['top']['type']) : null"
                       :caption="$stats['top'] ? 'Generated '.number_format($stats['top']['count']).' '.Str::plural('time', $stats['top']['count']) : 'No reports generated yet'" />
        <x-ui.kpi-tile label="Last generated" icon="download"
                       :value="$stats['latest']?->generated_at->diffForHumans()"
                       :caption="$stats['latest'] ? Str::headline($stats['latest']->report_type).' by '.($stats['latest']->generatedBy?->full_name ?? 'a deleted account') : 'No reports generated yet'" />
    </x-ui.kpi-row>

    {{-- ONE FLOW, TOP TO BOTTOM: choose the period, then download. The
         period sits above the reports it applies to, so the eye never has
         to cross the page between setting it and using it. --}}
    <x-ui.card class="mt-4" :flush="true" aria-labelledby="reports-heading">
        <div class="px-5 pt-5">
            <h2 id="reports-heading" class="text-sm font-semibold text-foreground">Download a Report</h2>
            <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs">Choose the period, then download any report as CSV or PDF.</p>
        </div>

        {{-- Step 1: the period. --}}
        <div class="mt-4 border-y border-border bg-muted/40 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Period">
                <span class="mr-1 text-xs font-semibold text-foreground">Period</span>
                @foreach (['all' => 'All Time', 'month' => 'This Month', 'quarter' => 'Last 3 Months', 'year' => 'Last 12 Months', 'calendar' => 'This Year'] as $key => $chip)
                    <button type="button" x-on:click="choose(@js($key))"
                            x-bind:aria-pressed="(preset === @js($key) || (@js($key) === 'all' && ! from && ! to && preset !== 'custom')).toString()"
                            x-bind:class="(preset === @js($key) || (@js($key) === 'all' && ! from && ! to && preset !== 'custom')) ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-foreground hover:bg-muted'"
                            class="inline-flex h-11 items-center rounded-full border px-3.5 text-[13px] font-medium transition-colors lg:h-7 lg:px-3 lg:text-xs">
                        {{ $chip }}
                    </button>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="d-fieldset-legend">From</label>
                    <input id="from" type="date" x-model="from" x-on:input="preset = 'custom'" x-bind:max="to || null" class="d-input datum w-44" aria-describedby="range-help">
                </div>
                <div>
                    <label for="to" class="d-fieldset-legend">To</label>
                    <input id="to" type="date" x-model="to" x-on:input="preset = 'custom'" x-bind:min="from || null" class="d-input datum w-44" aria-describedby="range-help">
                </div>
                <p id="range-help" class="d-label pb-1.5 sm:max-w-xs">
                    The period is saved with each report, so anyone can see exactly what was included.
                </p>
            </div>
        </div>

        {{-- Step 2: the reports, each covering the period chosen above. --}}
        <p class="px-5 pt-4 text-[13px] text-muted-foreground lg:text-xs" aria-live="polite">
            Reports below will cover <span class="datum font-semibold text-foreground" x-text="label">all records</span>.
        </p>
        <ul class="divide-y divide-border">
            @foreach ($this->reports as $report)
                <li class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-8">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-foreground lg:text-xs">{{ $report->title() }}</h3>
                        <p class="mt-0.5 max-w-[70ch] text-[13px] leading-relaxed text-muted-foreground lg:text-xs">{{ $report->description() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reports.csv', $report->key()) }}"
                           x-bind:href="@js(route('reports.csv', $report->key())) + query()"
                           class="d-btn d-btn-outline">
                            <x-ui.icon name="download" class="size-4 lg:size-3.5" /> CSV<span class="sr-only">, {{ $report->title() }}</span>
                        </a>
                        <a href="{{ route('reports.pdf', $report->key()) }}"
                           x-bind:href="@js(route('reports.pdf', $report->key())) + query()"
                           class="d-btn d-btn-outline">
                            <x-ui.icon name="download" class="size-4 lg:size-3.5" /> PDF<span class="sr-only">, {{ $report->title() }}</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    {{-- The audit trail. --}}
    <x-ui.card class="mt-4" :flush="true" aria-labelledby="history-heading">
        <div class="px-5 py-5">
            <h2 id="history-heading" class="text-sm font-semibold text-foreground">Report History</h2>
            <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs">Every report generated, who ran it, and exactly which filters were used.</p>
        </div>

        @if ($history->isEmpty())
            <x-empty-state :bare="true" class="border-t border-border py-10"
                           title="No reports have been generated yet"
                           description="Download one above and it will appear here."
                           icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        @else
            <table class="d-table block w-full border-t border-border md:table md:border-separate md:border-spacing-0">
                <thead class="hidden md:table-header-group">
                    <tr>
                        <th scope="col" class="border-b border-border {{ $th }}">Report</th>
                        <th scope="col" class="border-b border-border {{ $th }}">Format</th>
                        <th scope="col" class="border-b border-border {{ $th }} text-right">Rows</th>
                        <th scope="col" class="border-b border-border {{ $th }}">Filters used</th>
                        <th scope="col" class="border-b border-border {{ $th }}">Generated by</th>
                        <th scope="col" class="border-b border-border {{ $th }}">When</th>
                    </tr>
                </thead>
                <tbody class="block divide-y divide-border px-5 md:table-row-group md:divide-y-0 md:px-0" wire:loading.remove wire:target="{{ $pageTargets }}">
                    @foreach ($history as $entry)
                        <tr class="block py-4 transition-colors hover:bg-muted/60 md:table-row md:py-0">
                            <td class="{{ $td }} text-sm font-semibold text-foreground lg:text-xs">
                                {{ Str::headline($entry->report_type) }}
                            </td>
                            {{-- strtoupper is applied to the format and NOT to the
                                 fallback, so a missing format reads "Not recorded"
                                 rather than shouting NOT RECORDED. --}}
                            <td class="mt-2 inline-block border-b-0 px-0 py-0 md:mt-0 md:table-cell md:border-b md:border-border md:px-5 md:py-2.5 md:align-top">
                                <span class="d-badge d-badge-soft d-badge-neutral">
                                    {{ $entry->format ? strtoupper($entry->format) : 'Not recorded' }}
                                </span>
                            </td>
                            <td class="ml-3 inline-block border-b-0 px-0 py-0 text-[13px] text-muted-foreground md:ml-0 md:table-cell md:border-b md:border-border md:px-5 md:py-2.5 md:text-right md:align-top lg:text-xs">
                                <span class="datum text-foreground">{{ number_format((int) $entry->row_count) }}</span><span class="md:hidden"> rows</span>
                            </td>
                            <td class="mt-2 {{ $td }} text-[13px] text-muted-foreground lg:text-xs">
                                @if (empty($entry->parameters))
                                    No filters (all records)
                                @else
                                    <span class="flex flex-wrap gap-1.5">
                                        @foreach ($entry->parameters as $key => $value)
                                            @if ($value !== null && $value !== '')
                                                <span class="inline-block rounded-sm bg-muted px-1.5 py-0.5 text-foreground">
                                                    {{ Str::headline($key) }}: <span class="datum">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </span>
                                @endif
                            </td>
                            <td class="mt-2 {{ $td }} text-[13px] text-muted-foreground lg:text-xs">
                                <span class="md:hidden">By </span>{{ $entry->generatedBy?->full_name ?? 'Deleted account' }}
                            </td>
                            <td class="mt-1 {{ $td }} text-[13px] text-muted-foreground md:whitespace-nowrap lg:text-xs">
                                <span class="datum">{{ $entry->generated_at->format('j M Y, g:i a') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <x-table-skeleton :cols="6" :rows="4" wire:loading wire:target="{{ $pageTargets }}" />
            </table>

            <div class="px-5 pb-5">
                <x-ui.table-pagination :paginator="$history" class="mt-0" />
            </div>
        @endif
    </x-ui.card>
</div>
