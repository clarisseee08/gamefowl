{{--
    Vaccination schedule - thesis objective (c).

    Two lists, in the order the farm works them: what is already late, then
    what is coming up. Both are derived from next_due_date at read time, so
    this screen can never disagree with the records behind it.

    This is the most date-dense screen in the system, so every date, interval
    and count is `.datum` - mono + tabular-nums. A "Was Due" column whose digits
    do not line up is unreadable at a glance, which is the only way this screen
    is ever read.

    State is never carried by colour alone: overdue rows say "late", upcoming
    rows say how many days, and both sections are titled in words.
--}}
<div>
    <div class="mb-8 border-b border-rule-strong pb-6 sm:flex sm:items-end sm:justify-between sm:gap-8">
        <div>
            <h1 class="text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-ink">Vaccination Schedule</h1>
            <p class="mt-2 max-w-[65ch] text-[15px] leading-relaxed text-ink-80">
                Follow-ups that are late, and follow-ups falling due in the next
                <span class="datum">{{ $this->warningDays() }}</span> days.
            </p>
        </div>

        <div class="mt-5 sm:mt-0 sm:shrink-0">
            <a href="{{ route('health.index') }}" wire:navigate class="btn-secondary">All Health Records</a>
        </div>
    </div>

    {{-- Two numbers, big enough to read across a room. This is the question
         the screen exists to answer. The left rule is the whole of the
         decoration: a ledger marks a column, it does not tint it. --}}
    <div class="mb-10 grid gap-4 sm:grid-cols-2">
        <div class="card border-l-[3px] border-l-alert p-5">
            <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Overdue</p>
            <p class="datum mt-2 text-[44px] font-semibold leading-none tracking-[-0.02em] text-alert">{{ number_format($this->overdueCount) }}</p>
            <p class="mt-3 text-[15px] leading-snug text-ink-80">
                {{ $this->overdueCount === 1 ? 'bird is' : 'birds are' }} past a follow-up date.
            </p>
        </div>

        <div class="card border-l-[3px] border-l-warn p-5">
            <p class="text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Due in the next {{ $this->warningDays() }} days</p>
            <p class="datum mt-2 text-[44px] font-semibold leading-none tracking-[-0.02em] text-warn">{{ number_format($this->dueSoonCount) }}</p>
            <p class="mt-3 text-[15px] leading-snug text-ink-80">
                {{ $this->dueSoonCount === 1 ? 'follow-up is' : 'follow-ups are' }} coming up.
            </p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Overdue - most overdue first                                     --}}
    {{-- ---------------------------------------------------------------- --}}
    <section class="mb-10" aria-labelledby="overdue-heading">
        <h2 id="overdue-heading" class="mb-3 flex flex-wrap items-center gap-2.5 text-[22px] font-semibold tracking-[-0.01em] text-ink">
            Overdue
            <span class="badge badge-alert datum">{{ number_format($this->overdueCount) }}</span>
        </h2>

        <div class="card overflow-hidden">
            @if ($this->overdue->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-[18px] font-medium text-ink">Nothing is overdue.</p>
                    <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-ink-80">
                        Every follow-up date on file is still in the future. Nothing to do here today.
                    </p>
                </div>
            @else
                <table class="w-full text-left">
                    <thead class="hidden bg-pearl sm:table-header-group">
                        <tr class="border-b border-rule-strong">
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Bird</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Record Type</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Product</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Was Due</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">How Late</th>
                            <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="block divide-y divide-divider sm:table-row-group">
                        @foreach ($this->overdue as $record)
                            @php $daysLate = abs((int) $record->daysUntilDue()); @endphp

                            <tr wire:key="overdue-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                                <td class="block sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Bird</span>
                                    <p class="text-[15px] font-medium leading-snug text-ink">{{ $record->broodcock->name }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                    :band="$record->broodcock->band_number"
                                                    size="xs" />
                                        @if ($record->broodcock->bloodline)
                                            <span class="text-[12px] text-ink-80">{{ $record->broodcock->bloodline }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Record Type</span>
                                    <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Product</span>
                                    <span class="text-[15px] text-ink">{{ $record->product_name ?: '—' }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Was Due</span>
                                    <span class="datum text-[15px] text-ink">{{ $record->next_due_date->format('d M Y') }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">How Late</span>
                                    <span class="badge badge-alert">
                                        <span class="datum">{{ $daysLate }}</span> {{ Str::plural('day', $daysLate) }} late
                                    </span>
                                </td>

                                <td class="mt-4 block border-t border-hairline pt-3 sm:mt-0 sm:table-cell sm:border-0 sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
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

                <div class="border-t border-hairline bg-pearl px-4 py-2.5">
                    {{ $this->overdue->links() }}
                </div>
            @endif
        </div>
    </section>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Due soon - soonest first                                         --}}
    {{-- ---------------------------------------------------------------- --}}
    <section aria-labelledby="upcoming-heading">
        <h2 id="upcoming-heading" class="mb-3 flex flex-wrap items-center gap-2.5 text-[22px] font-semibold tracking-[-0.01em] text-ink">
            Due in the next {{ $this->warningDays() }} days
            <span class="badge badge-warn datum">{{ number_format($this->dueSoonCount) }}</span>
        </h2>

        <div class="card overflow-hidden">
            @if ($this->dueSoon->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-[18px] font-medium text-ink">Nothing is due in the next {{ $this->warningDays() }} days.</p>
                    <p class="mx-auto mt-2 max-w-[52ch] text-[15px] leading-relaxed text-ink-80">
                        When you record a vaccination or deworming, fill in its
                        &ldquo;Next Due Date&rdquo; and the follow-up will appear here.
                    </p>
                </div>
            @else
                <table class="w-full text-left">
                    <thead class="hidden bg-pearl sm:table-header-group">
                        <tr class="border-b border-rule-strong">
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Bird</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Record Type</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Product</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Due On</th>
                            <th scope="col" class="px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">In</th>
                            <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="block divide-y divide-divider sm:table-row-group">
                        @foreach ($this->dueSoon as $record)
                            @php $daysLeft = (int) $record->daysUntilDue(); @endphp

                            <tr wire:key="upcoming-{{ $record->id }}" class="block p-4 sm:table-row sm:p-0 sm:align-top sm:hover:bg-pearl">
                                <td class="block sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Bird</span>
                                    <p class="text-[15px] font-medium leading-snug text-ink">{{ $record->broodcock->name }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <x-band-tag :bloodline="$record->broodcock->bloodline"
                                                    :band="$record->broodcock->band_number"
                                                    size="xs" />
                                        @if ($record->broodcock->bloodline)
                                            <span class="text-[12px] text-ink-80">{{ $record->broodcock->bloodline }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Record Type</span>
                                    <span class="badge {{ $record->record_type->badgeClasses() }}">{{ $record->record_type->label() }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Product</span>
                                    <span class="text-[15px] text-ink">{{ $record->product_name ?: '—' }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">Due On</span>
                                    <span class="datum text-[15px] text-ink">{{ $record->next_due_date->format('d M Y') }}</span>
                                </td>

                                <td class="mt-3 block sm:mt-0 sm:table-cell sm:px-4 sm:py-3 sm:whitespace-nowrap">
                                    <span class="mb-1 block text-[11px] font-medium uppercase tracking-[0.06em] text-ink-80 sm:hidden">In</span>
                                    <span class="badge badge-warn">
                                        @if ($daysLeft === 0)
                                            Today
                                        @else
                                            <span class="datum">{{ $daysLeft }}</span> {{ Str::plural('day', $daysLeft) }}
                                        @endif
                                    </span>
                                </td>

                                <td class="mt-4 block border-t border-hairline pt-3 sm:mt-0 sm:table-cell sm:border-0 sm:px-4 sm:py-3 sm:text-right sm:whitespace-nowrap">
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

                <div class="border-t border-hairline bg-pearl px-4 py-2.5">
                    {{ $this->dueSoon->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
