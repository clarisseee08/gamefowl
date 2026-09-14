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

        <div class="w-full sm:w-56">
            {{-- "All requests" first, and it is not a nicety. The page opens on
                 Awaiting reply, which is right for working a queue - but with no
                 way to widen it, a farm whose queue is momentarily empty gets a
                 screen reading "No visit requests to show" while it is in fact
                 holding a confirmed visit for Tuesday. Nothing on the page
                 admitted the other records existed. --}}
            <x-form-select id="status" label="Showing" wire:model.live="status">
                <option value="">All requests</option>
                @foreach ($this->statusOptions() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </x-form-select>
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
                    <thead class="bg-muted">
                        <tr>
                            {{-- Four of the six sort; About and Actions do not.
                                 About is a relationship rather than a column on
                                 this table, and Actions is not data. A header
                                 that looks pressable and does nothing is worse
                                 than one that plainly is not. --}}
                            <x-sort-control field="name" label="Who"
                                            :current="$sortBy" :direction="$sortDirection"
                                            wire:click="sort('name')" />
                            <x-sort-control field="preferred_date" label="When"
                                            :current="$sortBy" :direction="$sortDirection"
                                            wire:click="sort('preferred_date')" />
                            <x-sort-control field="party_size" label="Party"
                                            :current="$sortBy" :direction="$sortDirection"
                                            wire:click="sort('party_size')" />
                            <th scope="col" class="px-4 py-2.5 text-left text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">About</th>
                            <x-sort-control field="status" label="Status"
                                            :current="$sortBy" :direction="$sortDirection"
                                            wire:click="sort('status')" />
                            <th scope="col" class="px-4 py-2.5 text-right text-[11px] font-medium uppercase tracking-[0.06em] text-muted-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.remove wire:target="status">
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

                                {{-- WHAT EACH STATE CAN ACTUALLY DO.

                                     These three conditions used to be written as
                                     "not Confirmed", "is Confirmed" and "not
                                     Declined", which is correct for a Pending row
                                     and wrong for a Completed one: a visit that has
                                     already happened matched both "not Confirmed"
                                     and "not Declined", so the farm was offered
                                     Confirm and Decline on a visit the family had
                                     already made.

                                     It was unreachable until now - the only way to
                                     see a Completed row was to pick that status
                                     deliberately, and the screen opened on Awaiting
                                     reply. Adding "All requests" is what would have
                                     put it in front of someone. So the states are
                                     written out, one row each, rather than inferred
                                     from what a status is NOT. --}}
                                @php
                                    $status = $request->status;
                                    $canConfirm = in_array($status, [
                                        \App\Enums\AppointmentStatus::Pending,
                                        \App\Enums\AppointmentStatus::Declined,
                                    ], true);
                                    $canComplete = $status === \App\Enums\AppointmentStatus::Confirmed;
                                    $canDecline = in_array($status, [
                                        \App\Enums\AppointmentStatus::Pending,
                                        \App\Enums\AppointmentStatus::Confirmed,
                                    ], true);
                                @endphp

                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($canConfirm)
                                            <button type="button" wire:click="confirm({{ $request->id }})"
                                                    class="btn-secondary">Confirm</button>
                                        @endif
                                        @if ($canComplete)
                                            <button type="button" wire:click="markVisited({{ $request->id }})"
                                                    class="btn-secondary">They came</button>
                                        @endif
                                        @if ($canDecline)
                                            <button type="button" wire:click="decline({{ $request->id }})"
                                                    class="btn-quiet">Decline</button>
                                        @endif

                                        {{-- Owner only, by the Policy rather than by this
                                             screen: staff can decide a request, only an
                                             owner can erase one. --}}
                                        @can('delete', $request)
                                            <button type="button" wire:click="confirmDelete({{ $request->id }})"
                                                    class="btn-quiet text-destructive">Delete</button>
                                        @endcan

                                        {{-- A completed visit is history, not a queue
                                             item. Saying so beats an empty cell that
                                             reads as a rendering fault. --}}
                                        @unless ($canConfirm || $canComplete || $canDecline || auth()->user()?->can('delete', $request))
                                            <span class="text-[14px] text-muted-foreground">Nothing to do</span>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <x-table-skeleton :cols="6" wire:loading wire:target="status" />
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->requests->links() }}
        </div>
    @endif

    {{-- Destructive actions always confirm, and the dialog names the exact
         request so nobody erases the wrong family's visit.

         THE WARNING IS DIFFERENT FROM EVERY OTHER DELETE IN THIS APPLICATION,
         and deliberately. A health record, a bird and a breeding record all
         soft-delete and can be restored by the owner, so their dialogs say so.
         Appointment has no SoftDeletes trait and appointments has no deleted_at
         column - the Policy confirms it by returning false from restore() and
         forceDelete(). There is nothing to restore from, so this says the one
         true thing instead of borrowing reassuring copy from a screen where it
         happens to be accurate. --}}
    @if ($this->requestPendingDeletion)
        @php $pending = $this->requestPendingDeletion; @endphp

        <div class="fixed inset-0 z-50 flex items-end justify-center bg-foreground/50 p-4 sm:items-center"
             x-data x-trap.noscroll="true" @keydown.escape.window="$el.querySelector('.btn-secondary')?.click()"
             role="dialog" aria-modal="true" aria-labelledby="delete-visit-title">
            <div class="w-full max-w-lg rounded-[4px] border border-border bg-card p-6">
                <h2 id="delete-visit-title" class="text-[22px] font-semibold tracking-[-0.01em] text-foreground">
                    Delete this visit request?
                </h2>

                <p class="mt-3 text-[15px] leading-relaxed text-muted-foreground">
                    You are about to delete the request from
                    <strong class="font-medium text-foreground">{{ $pending->name }}</strong>
                    (<span class="datum text-foreground">{{ $pending->contact_number }}</span>)
                    for <strong class="datum font-medium text-foreground">{{ $pending->preferred_date->format('d M Y') }}</strong>.
                </p>

                <p class="mt-2 text-[15px] leading-relaxed text-muted-foreground">
                    This one cannot be undone &mdash; a visit request is not kept in the
                    farm's history the way a bird or a health record is. If you only want
                    it out of the queue, Decline it instead.
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="btn-secondary">
                        No, keep it
                    </button>
                    <button type="button" wire:click="delete" class="btn-danger" wire:loading.attr="disabled">
                        Yes, delete this request
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
