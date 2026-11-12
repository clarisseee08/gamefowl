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

    The figures from the audit trail first, then the reports - the one option
    they share, the period, in the card's header - then the history as a
    table card.

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
        preset: @js($from || $to ? 'custom' : 'all'),
        init() { this.$watch('preset', (key) => this.apply(key)); },
        // A download is built on the server before the browser shows anything,
        // so the link takes the console's busy spinner for a few seconds and
        // cannot be pressed twice. There is no 'finished' event for a file.
        busy(link) {
            link.setAttribute('data-loading', 'true');
            setTimeout(() => link.removeAttribute('data-loading'), 4000);
        },
        iso(date) { return date.toLocaleDateString('en-CA'); },
        apply(key) {
            const today = new Date();
            const start = new Date(today);
            if (key === 'all') { this.from = ''; this.to = ''; }
            if (key === 'month') { start.setDate(1); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'quarter') { start.setMonth(today.getMonth() - 3); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'year') { start.setFullYear(today.getFullYear() - 1); this.from = this.iso(start); this.to = this.iso(today); }
            if (key === 'calendar') { this.from = today.getFullYear() + '-01-01'; this.to = this.iso(today); }
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

    {{-- DOWNLOAD A REPORT: the period, picked once in the header, then the
         reports as tiles, each with its two downloads. The period is a
         dropdown like every other choice in the console; "Custom range"
         opens the two dates. --}}
    <x-ui.card class="mt-4" :flush="true" aria-labelledby="reports-heading">
        <div class="flex flex-col gap-4 border-b border-border px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h2 id="reports-heading" class="text-sm font-semibold text-foreground">Download a Report</h2>
                <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs" aria-live="polite">
                    Covering <span class="datum font-semibold text-foreground" x-text="label">all records</span>
                </p>
            </div>

            <div class="w-full sm:w-56">
                <x-ui.select id="report-period" label="Period" :label-hidden="true" x-model="preset"
                             :options="['all' => 'All time', 'month' => 'This month', 'quarter' => 'Last 3 months', 'year' => 'Last 12 months', 'calendar' => 'This year', 'custom' => 'Custom range…']" />
            </div>
        </div>

        <div x-show="preset === 'custom'" x-cloak x-collapse
             class="border-b border-border bg-muted/40">
            <div class="flex flex-wrap items-end gap-3 px-5 py-4">
                <div>
                    <label for="from" class="d-fieldset-legend">From</label>
                    <x-ui.date-picker id="from" x-model="from" max-from="to" class="w-44" />
                </div>
                <div>
                    <label for="to" class="d-fieldset-legend">To</label>
                    <x-ui.date-picker id="to" x-model="to" min-from="from" class="w-44" />
                </div>
            </div>
        </div>

        <ul class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->reports as $report)
                <li class="flex flex-col rounded-md border border-border bg-card p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-foreground" aria-hidden="true">
                            <x-ui.icon name="file-text" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-foreground lg:text-xs">{{ $report->title() }}</h3>
                            <p class="mt-1 text-[13px] leading-relaxed text-muted-foreground lg:text-xs">{{ $report->description() }}</p>
                        </div>
                    </div>
                    <div class="mt-auto grid grid-cols-2 gap-2 pt-4">
                        <a href="{{ route('reports.pdf', $report->key()) }}"
                           x-bind:href="@js(route('reports.pdf', $report->key())) + query()"
                           x-on:click="busy($el)"
                           class="d-btn d-btn-outline">
                            <x-ui.icon name="download" class="size-4 lg:size-3.5" /> PDF<span class="sr-only">, {{ $report->title() }}</span>
                        </a>
                        <a href="{{ route('reports.csv', $report->key()) }}"
                           x-bind:href="@js(route('reports.csv', $report->key())) + query()"
                           x-on:click="busy($el)"
                           class="d-btn d-btn-outline">
                            <x-ui.icon name="download" class="size-4 lg:size-3.5" /> CSV<span class="sr-only">, {{ $report->title() }}</span>
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
