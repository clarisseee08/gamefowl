{{--
    One bird's health history, newest first.

    Rendered as a panel so it drops straight into the broodcock detail page:

        <livewire:health.broodcock-health-history :broodcock="$broodcock" />
--}}
<div class="card overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-4 py-4 sm:px-6">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Health History</h2>
            <p class="mt-1 text-sm text-gray-600">
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
                'Overdue' => 'bg-rose-50 text-rose-800 ring-rose-200',
                'Due soon' => 'bg-amber-50 text-amber-800 ring-amber-200',
                default => 'bg-sky-50 text-sky-800 ring-sky-200',
            };
        @endphp

        <div class="border-b border-gray-200 px-4 py-3 sm:px-6">
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
            <p class="text-base font-semibold text-gray-900">No health records for this bird yet.</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-600">
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
            <thead class="hidden bg-gray-50 text-xs uppercase tracking-wide text-gray-600 sm:table-header-group">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Check-up Date</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Record Type</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Product / Condition</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Next Due Date</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                </tr>
            </thead>

            <tbody class="block divide-y divide-gray-200 sm:table-row-group">
                @foreach ($this->rows as $record)
                    @php
                        $state = $record->scheduleState();
                        $stateClasses = match ($state) {
                            'Overdue' => 'bg-rose-100 text-rose-800 ring-rose-600/20',
                            'Due soon' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
                            'Scheduled' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
                            default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
                        };
                    @endphp

                    <tr wire:key="history-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-gray-50">
                        <td class="block sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 sm:hidden">Check-up Date</span>
                            <span class="font-medium text-gray-900">{{ $record->checkup_date->format('d M Y') }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 sm:hidden">Record Type</span>
                            <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 sm:hidden">Product / Condition</span>
                            <p class="text-gray-900">{{ $record->product_name ?: '—' }}</p>
                            @if ($record->dosage)
                                <p class="text-xs text-gray-500">Dosage: {{ $record->dosage }}</p>
                            @endif
                            @if ($record->condition)
                                <p class="text-xs text-gray-500">Condition: {{ $record->condition }}</p>
                            @endif
                            {{-- Internal remarks are for farm staff. Customers are
                                 promised health status, not the farm's notes. --}}
                            @can('viewRemarks', $record)
                                @if ($record->remarks)
                                    <p class="mt-1 text-xs text-gray-500"><span class="font-medium">Remarks:</span> {{ $record->remarks }}</p>
                                @endif
                            @endcan
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 sm:hidden">Next Due Date</span>
                            <span class="text-gray-900">{{ $record->next_due_date?->format('d M Y') ?? 'None' }}</span>
                        </td>

                        <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 sm:hidden">Status</span>
                            <span class="badge {{ $stateClasses }}">{{ $state }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($this->rows->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $this->rows->links() }}
            </div>
        @endif
    @endif
</div>
