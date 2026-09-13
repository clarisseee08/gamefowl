{{--
    One bird's health history, newest first.

    Rendered as a panel so it drops straight into the broodcock detail page:

        <livewire:health.broodcock-health-history :broodcock="$broodcock" />

    It is a Console surface embedded in another one, so it carries no page
    furniture of its own: a hairline head, a ruled callout, and a ledger table
    whose dates are `.datum` so the check-up column reads down.
--}}
<div class="card overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-border px-4 py-4 sm:px-5">
        <div class="min-w-0">
            <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">Health History</h2>
            <p class="mt-1 max-w-[65ch] text-[15px] leading-relaxed text-muted-foreground">
                Vaccinations, medications and check-ups for {{ $broodcock->name }}, newest first.
            </p>
            {{-- The bird this panel belongs to, stated the way the system states
                 every bird: its band, in its bloodline's colour, with the
                 bloodline spelled out so colour is never the only channel. --}}
            <div class="mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                <x-band-tag :bloodline="$broodcock->bloodline" :band="$broodcock->band_number" size="xs" />
                @if ($broodcock->bloodline)
                    <span class="text-[12px] text-muted-foreground">{{ $broodcock->bloodline }}</span>
                @endif
            </div>
        </div>

        @can('create', \App\Models\HealthRecord::class)
            <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" wire:navigate class="btn-primary shrink-0">
                Add Health Record
            </a>
        @endcan
    </div>

    {{-- The one thing the keeper needs at a glance: what is this bird waiting on? --}}
    @if ($this->nextFollowUp)
        @php
            $followUp = $this->nextFollowUp;
            $followUpState = $followUp->scheduleState();
            // A left rule and a desaturated wash - the state is also written out
            // in words at the end of the sentence, so hue is never load-bearing.
            $followUpClasses = match ($followUpState) {
                'Overdue' => 'border-destructive bg-destructive-bg text-destructive',
                'Due soon' => 'border-warning bg-warning-bg text-warning',
                default => 'border-info bg-info-bg text-info',
            };
        @endphp

        <div class="border-b border-border px-4 py-3 sm:px-5">
            <p class="rounded-[4px] border-l-2 px-3 py-2 text-[15px] leading-snug {{ $followUpClasses }}">
                <span class="font-medium">Next follow-up:</span>
                {{ $followUp->record_type->label() }}
                @if ($followUp->product_name)
                    ({{ $followUp->product_name }})
                @endif
                on <span class="datum">{{ $followUp->next_due_date->format('d M Y') }}</span> &mdash; {{ $followUpState }}.
            </p>
        </div>
    @endif

    @if ($this->rows->isEmpty())
        <div class="px-6 py-14 text-center">
            <p class="text-[18px] font-medium text-foreground">No health records for this bird yet.</p>
            <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-muted-foreground">
                @can('create', \App\Models\HealthRecord::class)
                    Click &ldquo;Add Health Record&rdquo; above to log this bird's first
                    vaccination, deworming or check-up.
                @else
                    Nothing has been recorded for {{ $broodcock->name }} so far.
                @endcan
            </p>
        </div>
    @else
        {{-- The card clips, so a table wider than the panel scrolls inside its
             own container rather than losing its last column. --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="hidden bg-muted sm:table-header-group">
                    <tr class="border-b border-border">
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Check-up Date</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Record Type</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Product / Condition</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Next Due Date</th>
                        <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Status</th>
                        <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Actions</th>
                    </tr>
                </thead>

                <tbody class="block divide-y divide-border sm:table-row-group">
                    @foreach ($this->rows as $record)
                        @php
                            $state = $record->scheduleState();
                            $stateClasses = match ($state) {
                                'Overdue' => 'badge-alert',
                                'Due soon' => 'badge-warn',
                                'Scheduled' => 'badge-info',
                                default => 'badge-neutral',
                            };
                        @endphp

                        <tr wire:key="history-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-muted">
                            <td class="block sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Check-up Date</span>
                                <span class="datum text-[15px] font-medium text-foreground">{{ $record->checkup_date->format('d M Y') }}</span>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Record Type</span>
                                <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Product / Condition</span>
                                <p class="text-[15px] leading-snug text-foreground">{{ $record->product_name ?: 'Not recorded' }}</p>
                                @if ($record->dosage)
                                    <p class="mt-0.5 text-[12px] text-muted-foreground">Dosage: <span class="datum">{{ $record->dosage }}</span></p>
                                @endif
                                @if ($record->condition)
                                    <p class="mt-0.5 text-[12px] text-muted-foreground">Condition: {{ $record->condition }}</p>
                                @endif
                                {{-- Internal remarks are for farm staff. Customers are
                                     promised health status, not the farm's notes. --}}
                                @can('viewRemarks', $record)
                                    @if ($record->remarks)
                                        <p class="mt-1.5 max-w-[40ch] border-l-2 border-border pl-2 text-[12px] leading-snug text-muted-foreground"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                    @endif
                                @endcan
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Next Due Date</span>
                                @if ($record->next_due_date)
                                    <span class="datum text-[15px] text-foreground">{{ $record->next_due_date->format('d M Y') }}</span>
                                @else
                                    <span class="text-[15px] text-muted-foreground">None</span>
                                @endif
                            </td>

                            <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground sm:hidden">Status</span>
                                <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                            </td>

                            <td class="mt-4 block border-t border-border pt-3 sm:mt-0 sm:table-cell sm:border-0 sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
                                @can('update', $record)
                                    <a href="{{ route('health.edit', $record) }}" wire:navigate class="btn-secondary">
                                        Edit<span class="sr-only">, {{ $record->record_type->label() }} on {{ $record->checkup_date->format('d M Y') }}</span>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($this->rows->hasPages())
            <div class="border-t border-border bg-muted px-4 py-2.5">
                {{ $this->rows->links() }}
            </div>
        @endif
    @endif
</div>
