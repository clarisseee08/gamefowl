@php
    $query = $this->exportQuery();
    $history = $this->history;
    $pageTargets = 'gotoPage,nextPage,previousPage';

    $th = 'px-4 py-3 text-left text-[13px] font-semibold text-muted-foreground';
    $td = 'block md:table-cell md:border-b md:border-border md:px-4 md:py-3 md:align-top';
    $headingClass = 'font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground';
@endphp

{{--
    THE REPORTS HUB.

    The anchor block counts what the audit trail holds. Below it, the one
    option every report shares (the date range) as a one-column form, then the
    reports as rows with their two downloads, then the history as a table.

    The downloads are plain links, never wire:navigate - a CSV or PDF is a
    file, not a page, and a client-side visit to one quietly does nothing.
--}}
<div>
    <x-anchor-block eyebrow="Records" title="Reports"
                    :figure="number_format($history->total())" figure-label="reports generated">
        Choose a date range, then download any report as a spreadsheet (CSV) or a
        printable document (PDF). Every report you generate is recorded below.
    </x-anchor-block>

    {{-- The one option, applied to every report --}}
    <section class="mt-10 max-w-[40rem]" aria-labelledby="range-heading">
        <h2 id="range-heading" class="border-b border-border pb-3 {{ $headingClass }}">Date range</h2>

        <div class="mt-5 space-y-5">
            <div>
                <label for="from" class="label">From</label>
                <input id="from" type="date" wire:model.live="from" class="input mt-1" aria-describedby="range-help">
            </div>
            <div>
                <label for="to" class="label">To</label>
                <input id="to" type="date" wire:model.live="to" class="input mt-1" aria-describedby="range-help">
                <p id="range-help" class="help">
                    Leave both dates empty to cover all records. The range you choose is saved with
                    the report, so anyone can see exactly what was included.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="useLastYear" class="btn-secondary">Last 12 months</button>
                @if ($from !== '' || $to !== '')
                    <button type="button" wire:click="clearRange" class="btn-quiet">Clear</button>
                @endif
            </div>
        </div>
    </section>

    {{-- The reports, as rows --}}
    <section class="mt-14" aria-labelledby="reports-heading" data-reveal>
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-1 border-b border-border pb-3">
            <h2 id="reports-heading" class="{{ $headingClass }}">Download a report</h2>
            <p class="text-[14px] text-muted-foreground">
                @if ($from !== '' || $to !== '')
                    Covering
                    @if ($from !== '') from <span class="datum text-foreground">{{ $from }}</span> @endif
                    @if ($to !== '') to <span class="datum text-foreground">{{ $to }}</span> @endif
                @else
                    Covering all records
                @endif
            </p>
        </div>

        <ul class="divide-y divide-border">
            @foreach ($this->reports as $report)
                <li class="grid gap-4 py-5 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-10">
                    <div class="min-w-0">
                        <h3 class="text-[16px] font-semibold text-foreground">{{ $report->title() }}</h3>
                        <p class="mt-1 max-w-[62ch] text-[15px] leading-relaxed text-muted-foreground">{{ $report->description() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('reports.csv', $report->key()).($query ? '?'.$query : '') }}"
                           class="btn-secondary">
                            Download CSV<span class="sr-only">, {{ $report->title() }}</span>
                        </a>
                        <a href="{{ route('reports.pdf', $report->key()).($query ? '?'.$query : '') }}"
                           class="btn-secondary">
                            Download PDF<span class="sr-only">, {{ $report->title() }}</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Audit trail --}}
    <section class="mt-14" aria-labelledby="history-heading" data-reveal>
        <h2 id="history-heading" class="{{ $headingClass }}">Report history</h2>
        <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
            Every report generated, who ran it, and exactly which filters were used.
        </p>

        @if ($history->isEmpty())
            <x-empty-state class="mt-6"
                           title="No reports have been generated yet"
                           description="Download one above and it will appear here."
                           icon="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        @else
            {{-- No overflow wrapper: the header sticks to <main>, the shell's
                 one scroll container. Below md each row stacks as a block
                 instead of scrolling sideways. --}}
            <table class="mt-6 block w-full border-t border-border md:table md:border-separate md:border-spacing-0 md:border-t-0">
                <thead class="hidden md:table-header-group">
                    <tr>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }}">Report</th>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }}">Format</th>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }} text-right">Rows</th>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }}">Filters used</th>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }}">Generated by</th>
                        <th scope="col" class="sticky top-0 z-10 border-b border-border bg-background {{ $th }}">When</th>
                    </tr>
                </thead>
                <tbody class="block divide-y divide-border md:table-row-group md:divide-y-0" wire:loading.remove wire:target="{{ $pageTargets }}">
                    @foreach ($history as $entry)
                        <tr class="block py-4 transition-colors hover:bg-muted/60 md:table-row md:py-0">
                            <td class="{{ $td }} text-[15px] font-semibold text-foreground">
                                {{ Str::headline($entry->report_type) }}
                            </td>
                            {{-- badge-neutral rather than the utilities it resolves
                                 to: the class vocabulary is a contract.

                                 strtoupper is applied to the format and NOT to the
                                 fallback, so a missing format reads "Not recorded"
                                 rather than shouting NOT RECORDED. --}}
                            <td class="mt-2 inline-block md:mt-0 md:table-cell md:border-b md:border-border md:px-4 md:py-3 md:align-top">
                                <span class="badge badge-neutral">
                                    {{ $entry->format ? strtoupper($entry->format) : 'Not recorded' }}
                                </span>
                            </td>
                            <td class="ml-3 inline-block text-[14px] text-muted-foreground md:ml-0 md:table-cell md:border-b md:border-border md:px-4 md:py-3 md:text-right md:align-top">
                                <span class="datum text-foreground">{{ number_format((int) $entry->row_count) }}</span><span class="md:hidden"> rows</span>
                            </td>
                            <td class="mt-2 {{ $td }} text-[13px] text-muted-foreground">
                                @if (empty($entry->parameters))
                                    No filters (all records)
                                @else
                                    <span class="flex flex-wrap gap-1.5">
                                        @foreach ($entry->parameters as $key => $value)
                                            @if ($value !== null && $value !== '')
                                                <span class="inline-block rounded-[4px] bg-muted px-1.5 py-0.5 text-foreground">
                                                    {{ Str::headline($key) }}: <span class="datum">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </span>
                                @endif
                            </td>
                            <td class="mt-2 {{ $td }} text-[14px] text-muted-foreground">
                                <span class="md:hidden">By </span>{{ $entry->generatedBy?->full_name ?? 'Deleted account' }}
                            </td>
                            <td class="mt-1 {{ $td }} text-[14px] text-muted-foreground md:whitespace-nowrap">
                                <span class="datum">{{ $entry->generated_at->format('j M Y, g:i a') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <x-table-skeleton :cols="6" :rows="4" wire:loading wire:target="{{ $pageTargets }}" />
            </table>

            @if ($history->hasPages())
                <div class="mt-6">{{ $history->links() }}</div>
            @endif
        @endif
    </section>
</div>
