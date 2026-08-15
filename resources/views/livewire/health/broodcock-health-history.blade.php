{{--
    One bird's health history, newest first.

    Rendered as a panel so it drops straight into the broodcock detail page:

        <livewire:health.broodcock-health-history :broodcock="$broodcock" />
--}}
<div class="card overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-hairline px-4 py-4 sm:px-6">
        <div>
            <h2 class="text-lg font-semibold text-ink">Health History</h2>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Vaccinations, medications and check-ups for {{ $broodcock->name }}, newest first.
            </p>
        </div>

        @can('create', \App\Models\HealthRecord::class)
            <a href="{{ route('health.create', ['broodcock' => $broodcock->id]) }}" wire:navigate class="btn-primary">
                Add Health Record
            </a>
        @endcan
    </div>

    {{-- The one thing the keeper needs at a glance: what is this bird waiting on? --}}
    @if ($this->nextFollowUp)
        @php
            $followUp = $this->nextFollowUp;
            $followUpState = $followUp->scheduleState();
            $followUpClasses = match ($followUpState) {
                'Overdue' => 'bg-alert-wash text-alert ring-alert/20',
                'Due soon' => 'bg-warn-wash text-warn ring-warn/20',
                default => 'bg-info-wash text-info ring-info/20',
            };
        @endphp

        <div class="border-b border-hairline px-4 py-3 sm:px-6">
            <p class="rounded-lg p-3 text-sm ring-1 {{ $followUpClasses }}">
                <span class="font-semibold">Next follow-up:</span>
                {{ $followUp->record_type->label() }}
                @if ($followUp->product_name)
                    ({{ $followUp->product_name }})
                @endif
                on {{ $followUp->next_due_date->format('d M Y') }} &mdash; {{ $followUpState }}.
            </p>
        </div>
    @endif

    @if ($this->rows->isEmpty())
        <div class="px-6 py-12 text-center">
            <p class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">No health records for this bird yet.</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-ink-80">
                @can('create', \App\Models\HealthRecord::class)
                    Click &ldquo;Add Health Record&rdquo; above to log this bird's first
                    vaccination, deworming or check-up.
                @else
                    Nothing has been recorded for {{ $broodcock->name }} so far.
                @endcan
            </p>
        </div>
    @else
        <table class="w-full text-left text-sm">
            <thead class="hidden bg-pearl text-xs uppercase tracking-wide text-ink-80 sm:table-header-group">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Check-up Date</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Record Type</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Product / Condition</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Next Due Date</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                </tr>
            </thead>

            <tbody class="block divide-y divide-divider sm:table-row-group">
                @foreach ($this->rows as $record)
                    @php
                        $state = $record->scheduleState();
                        $stateClasses = match ($state) {
                            'Overdue' => 'bg-alert-wash text-alert ring-alert/20',
                            'Due soon' => 'bg-warn-wash text-warn ring-warn/20',
                            'Scheduled' => 'bg-info-wash text-info ring-info/20',
                            default => 'bg-parchment text-ink-80 ring-hairline',
                        };
                    @endphp

                    <tr wire:key="history-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                        <td class="block sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                            <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Check-up Date</span>
                            <span class="font-medium text-ink">{{ $record->checkup_date->format('d M Y') }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Record Type</span>
                            <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Product / Condition</span>
                            <p class="text-ink">{{ $record->product_name ?: '—' }}</p>
                            @if ($record->dosage)
                                <p class="text-xs text-ink-48">Dosage: {{ $record->dosage }}</p>
                            @endif
                            @if ($record->condition)
                                <p class="text-xs text-ink-48">Condition: {{ $record->condition }}</p>
                            @endif
                            {{-- Internal remarks are for farm staff. Customers are
                                 promised health status, not the farm's notes. --}}
                            @can('viewRemarks', $record)
                                @if ($record->remarks)
                                    <p class="mt-1 text-xs text-ink-48"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                @endif
                            @endcan
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                            <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Next Due Date</span>
                            <span class="text-ink">{{ $record->next_due_date?->format('d M Y') ?? 'None' }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Status</span>
                            <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($this->rows->hasPages())
            <div class="border-t border-hairline px-4 py-3">
                {{ $this->rows->links() }}
            </div>
        @endif
    @endif
</div>
