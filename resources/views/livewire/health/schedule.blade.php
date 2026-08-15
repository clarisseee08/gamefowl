{{--
    Vaccination schedule - thesis objective (c).

    Two lists, in the order the farm works them: what is already late, then
    what is coming up. Both are derived from next_due_date at read time, so
    this screen can never disagree with the records behind it.
--}}
<div>
    <div class="mb-10 sm:flex sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">Vaccination Schedule</h1>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                Follow-ups that are late, and follow-ups falling due in the next
                {{ $this->warningDays() }} days.
            </p>
        </div>

        <div class="mt-4 sm:mt-0">
            <a href="{{ route('health.index') }}" wire:navigate class="btn-secondary">All Health Records</a>
        </div>
    </div>

    {{-- Two numbers, big enough to read across a room. This is the question
         the screen exists to answer. --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2">
        <div class="card border-l-4 border-alert p-6">
            <p class="text-sm font-medium text-ink-80">Overdue</p>
            <p class="mt-2 text-[40px] font-semibold tracking-[-0.022em] leading-[1.08] text-alert">{{ number_format($this->overdueCount) }}</p>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                {{ $this->overdueCount === 1 ? 'bird is' : 'birds are' }} past a follow-up date.
            </p>
        </div>

        <div class="card border-l-4 border-warn p-6">
            <p class="text-sm font-medium text-ink-80">Due in the next {{ $this->warningDays() }} days</p>
            <p class="mt-2 text-[40px] font-semibold tracking-[-0.022em] leading-[1.08] text-warn">{{ number_format($this->dueSoonCount) }}</p>
            <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
                {{ $this->dueSoonCount === 1 ? 'follow-up is' : 'follow-ups are' }} coming up.
            </p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Overdue - most overdue first                                     --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="mb-10" aria-labelledby="overdue-heading">
        <h2 id="overdue-heading" class="mb-3 text-lg font-semibold text-ink">
            Overdue
            <span class="ml-2 badge bg-alert-wash text-alert ring-alert/20">{{ number_format($this->overdueCount) }}</span>
        </h2>

        <div class="card overflow-hidden">
            @if ($this->overdue->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Nothing is overdue.</p>
                    <p class="mt-2 text-sm text-ink-80">
                        Every follow-up date on file is still in the future. Nothing to do here today.
                    </p>
                </div>
            @else
                <table class="w-full text-left text-sm">
                    <thead class="hidden bg-pearl text-xs uppercase tracking-wide text-ink-80 sm:table-header-group">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Bird</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Record Type</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Product</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Was Due</th>
                            <th scope="col" class="px-6 py-4 font-semibold">How Late</th>
                            <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="block divide-y divide-divider sm:table-row-group">
                        @foreach ($this->overdue as $record)
                            @php $daysLate = abs((int) $record->daysUntilDue()); @endphp

                            <tr wire:key="overdue-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                                <td class="block sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Bird</span>
                                    <p class="font-semibold text-ink">{{ $record->broodcock->name }}</p>
                                    <p class="text-xs text-ink-48">Band Number: {{ $record->broodcock->displayBand() }}</p>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Record Type</span>
                                    <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Product</span>
                                    <span class="text-ink">{{ $record->product_name ?: '—' }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Was Due</span>
                                    <span class="text-ink">{{ $record->next_due_date->format('d M Y') }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">How Late</span>
                                    <span class="badge bg-alert-wash text-alert ring-alert/20">
                                        {{ $daysLate }} {{ Str::plural('day', $daysLate) }} late
                                    </span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
                                    @can('create', \App\Models\HealthRecord::class)
                                        <a href="{{ route('health.create', ['broodcock' => $record->broodcock_id]) }}"
                                           wire:navigate
                                           class="btn-primary">
                                            Record Follow-up
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-hairline px-4 py-3">
                    {{ $this->overdue->links() }}
                </div>
            @endif
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Due soon - soonest first                                         --}}
    {{-- ---------------------------------------------------------------- --}}
    <section aria-labelledby="upcoming-heading">
        <h2 id="upcoming-heading" class="mb-3 text-lg font-semibold text-ink">
            Due in the next {{ $this->warningDays() }} days
            <span class="ml-2 badge bg-warn-wash text-warn ring-warn/20">{{ number_format($this->dueSoonCount) }}</span>
        </h2>

        <div class="card overflow-hidden">
            @if ($this->dueSoon->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-[21px] font-semibold tracking-[-0.01em] leading-[1.25] text-ink">Nothing is due in the next {{ $this->warningDays() }} days.</p>
                    <p class="mt-2 text-sm text-ink-80">
                        When you record a vaccination or deworming, fill in its
                        &ldquo;Next Due Date&rdquo; and the follow-up will appear here.
                    </p>
                </div>
            @else
                <table class="w-full text-left text-sm">
                    <thead class="hidden bg-pearl text-xs uppercase tracking-wide text-ink-80 sm:table-header-group">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Bird</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Record Type</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Product</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Due On</th>
                            <th scope="col" class="px-6 py-4 font-semibold">In</th>
                            <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="block divide-y divide-divider sm:table-row-group">
                        @foreach ($this->dueSoon as $record)
                            @php $daysLeft = (int) $record->daysUntilDue(); @endphp

                            <tr wire:key="upcoming-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                                <td class="block sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Bird</span>
                                    <p class="font-semibold text-ink">{{ $record->broodcock->name }}</p>
                                    <p class="text-xs text-ink-48">Band Number: {{ $record->broodcock->displayBand() }}</p>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Record Type</span>
                                    <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Product</span>
                                    <span class="text-ink">{{ $record->product_name ?: '—' }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">Due On</span>
                                    <span class="text-ink">{{ $record->next_due_date->format('d M Y') }}</span>
                                </td>

                                <td class="mt-2 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="text-[12px] font-medium uppercase tracking-[0.06em] text-ink-48 sm:hidden">In</span>
                                    <span class="badge bg-warn-wash text-warn ring-warn/20">
                                        {{ $daysLeft === 0 ? 'Today' : $daysLeft.' '.Str::plural('day', $daysLeft) }}
                                    </span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
                                    @can('create', \App\Models\HealthRecord::class)
                                        <a href="{{ route('health.create', ['broodcock' => $record->broodcock_id]) }}"
                                           wire:navigate
                                           class="btn-secondary">
                                            Record Follow-up
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-hairline px-4 py-3">
                    {{ $this->dueSoon->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
