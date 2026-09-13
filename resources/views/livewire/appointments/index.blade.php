{{--
    THE VISIT QUEUE — a console screen, not a catalog one.

    15px dense text, 44px touch targets, 16px inputs, 7:1 contrast. This is
    read standing in a shed on a phone, which is a different job from browsing
    photographs of birds.

    Dates, counts and phone numbers all take .datum: they are registry data and
    they sit in columns that have to align.
--}}
<div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Visit requests</h1>
            <p class="mt-2 max-w-[70ch] text-[15px] leading-relaxed text-muted-foreground">
                People who asked to come and see the birds. Confirming one records your
                decision here &mdash; it does not tell them anything, so ring the number
                they left.
            </p>
        </div>

        <div class="w-full sm:w-auto">
            <label for="status" class="label">Showing</label>
            <select id="status" wire:model.live="status" class="input mt-1 sm:w-56">
                @foreach ($this->statusOptions() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($this->requests->isEmpty())
        <div class="card p-10 text-center">
            <p class="text-[17px] text-foreground">No visit requests to show.</p>
            <p class="mt-2 text-[15px] text-muted-foreground">
                Requests made from the farm's front page appear here.
            </p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-hairline w-full text-left">
                    <thead class="bg-pearl">
                        <tr>
                            <th class="px-4 py-3 text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">Who</th>
                            <th class="px-4 py-3 text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">When</th>
                            <th class="px-4 py-3 text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">Party</th>
                            <th class="px-4 py-3 text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">About</th>
                            <th class="px-4 py-3 text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">Status</th>
                            <th class="px-4 py-3 text-right text-[13px] font-semibold uppercase tracking-[0.06em] text-ink-80">Decide</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->requests as $request)
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <p class="text-[15px] font-medium text-foreground">{{ $request->name }}</p>
                                    {{-- The phone number is the whole reply channel, so it
                                         is given the prominence of the name itself. --}}
                                    <p class="datum mt-0.5 text-[15px] text-foreground">{{ $request->contact_number }}</p>
                                    @if ($request->email)
                                        <p class="mt-0.5 text-[13px] text-muted-foreground">{{ $request->email }}</p>
                                    @endif
                                    @if ($request->message)
                                        <p class="mt-2 max-w-[44ch] text-[14px] leading-relaxed text-muted-foreground">
                                            {{ $request->message }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <p class="datum text-[15px] text-foreground">{{ $request->preferred_date->format('d M Y') }}</p>
                                    <p class="mt-0.5 text-[13px] text-muted-foreground">{{ ucfirst($request->preferred_time) }}</p>
                                </td>

                                <td class="datum px-4 py-4 align-top text-[15px] text-foreground">{{ $request->party_size }}</td>

                                <td class="px-4 py-4 align-top">
                                    @if ($request->broodcock)
                                        <x-band-tag :bloodline="$request->broodcock->bloodline"
                                                    :band="$request->broodcock->band_number" size="xs" />
                                        <p class="mt-1 text-[14px] text-foreground">{{ $request->broodcock->name }}</p>
                                    @else
                                        <span class="text-[14px] text-muted-foreground">The farm generally</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <span class="{{ $request->status->badgeClasses() }}">{{ $request->status->label() }}</span>
                                    @if ($request->handled_at)
                                        <p class="datum mt-1.5 text-[13px] text-muted-foreground">
                                            {{ $request->handled_at->format('d M Y') }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($request->status !== \App\Enums\AppointmentStatus::Confirmed)
                                            <button type="button" wire:click="confirm({{ $request->id }})"
                                                    class="btn-secondary">Confirm</button>
                                        @endif
                                        @if ($request->status === \App\Enums\AppointmentStatus::Confirmed)
                                            <button type="button" wire:click="markVisited({{ $request->id }})"
                                                    class="btn-secondary">They came</button>
                                        @endif
                                        @if ($request->status !== \App\Enums\AppointmentStatus::Declined)
                                            <button type="button" wire:click="decline({{ $request->id }})"
                                                    class="btn-quiet">Decline</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->requests->links() }}
        </div>
    @endif
</div>
