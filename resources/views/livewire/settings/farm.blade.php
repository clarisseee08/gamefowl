{{--
    THE FARM'S PUBLIC IDENTITY (EMS) - a console screen.

    An identity banner (the farm as the public meets it, and how complete its
    profile is), then the details as settings rows - what a field is for on
    the left, the field on the right - beside a preview drawn like the public
    contact card, and a note of every place these details are printed. A
    settings screen usually refuses to answer "where does this end up?"; this
    one answers it twice.

    Nothing is sent while typing: the fields bind without a round trip and the
    banner, the completeness bar and the preview follow them in the browser.
    Save is the one request.
--}}
@php
    $field = fn (string $name, string $extra = ''): string => trim('d-input w-full '.$extra.' '.($errors->has($name) ? 'd-input-error' : ''));
    $error = fn (string $name): string => $errors->has($name)
        ? '<p id="'.$name.'-error" class="mt-1.5 text-[13px] font-medium text-error lg:text-[11px]" role="alert">'.e($errors->first($name)).'</p>'
        : '';

    $rowClass = 'grid gap-3 border-t border-border px-5 py-5 md:grid-cols-[13rem_minmax(0,1fr)] md:gap-6';
    $rowTitle = 'text-sm font-semibold text-foreground lg:text-xs';
    $rowHelp = 'mt-0.5 text-[13px] leading-relaxed text-muted-foreground lg:text-xs';
    $iconInput = 'pointer-events-none absolute top-1/2 left-3 z-10 size-4 -translate-y-1/2 text-muted-foreground lg:size-3.5';
@endphp

<div x-data="{
        edited: false,
        get filled() { return ['phone', 'email', 'address', 'hours', 'visitor_note'].filter(key => ($wire[key] || '').trim() !== '').length },
     }">
    <x-ui.page-header title="Farm Profile"
                      description="How the farm is named and reached on the public pages, and at the foot of every report.">
        <x-slot:actions>
            <x-ui.button variant="outline" :href="route('public.visit')" target="_blank" rel="noopener">
                <x-ui.icon name="external-link" /> View Public Page
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- THE IDENTITY BANNER: the farm as the public meets it. --}}
    <x-ui.card class="mt-5" :flush="true">
        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center">
            <span class="inline-flex size-16 shrink-0 items-center justify-center rounded-full bg-muted ring-4 ring-muted/60">
                <x-brand-mark :size="56" class="rounded-full" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">Public farm profile</p>
                <p class="mt-0.5 truncate text-lg font-bold text-foreground" x-text="$wire.farm_name || 'Unnamed farm'">{{ $farm_name ?: 'Unnamed farm' }}</p>
                <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs" x-show="$wire.address" @unless ($address) x-cloak @endunless>
                    <x-ui.icon name="map-pin" class="mr-1 inline size-3.5 align-[-2px]" /><span x-text="$wire.address">{{ $address }}</span>
                </p>
            </div>
            <div class="w-full sm:w-56">
                <div class="flex items-baseline justify-between text-[13px] lg:text-xs">
                    <span class="font-medium text-foreground">Profile complete</span>
                    <span class="text-muted-foreground"><span class="datum font-semibold text-foreground" x-text="filled">{{ collect([$phone, $email, $address, $hours, $visitor_note])->filter()->count() }}</span> of <span class="datum">5</span></span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Profile complete" aria-valuemin="0" aria-valuemax="5" x-bind:aria-valuenow="filled">
                    <div class="h-full rounded-full bg-primary transition-[width] duration-300" x-bind:style="{ width: (filled / 5 * 100) + '%' }"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-muted-foreground" x-show="filled < 5">Fill in every detail so visitors know how to reach you.</p>
            </div>
        </div>
    </x-ui.card>

    <form wire:submit="save" x-on:input="edited = true" x-on:submit="edited = false" class="mt-4">
        <div class="grid gap-4 lg:grid-cols-3">
            {{-- THE DETAILS, as settings rows. --}}
            <x-ui.card class="lg:col-span-2" :flush="true">
                <div class="px-5 py-5">
                    <h2 class="text-sm font-semibold text-foreground">Farm Details</h2>
                    <p class="mt-0.5 text-[13px] text-muted-foreground lg:text-xs">Clear a field to stop showing it. The farm name is the exception &mdash; it appears on every page.</p>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Farm name <span class="text-destructive">*</span></p>
                        <p class="{{ $rowHelp }}">The sidebar, the browser tab and every PDF footer.</p>
                    </div>
                    <div>
                        <label for="farm_name" class="sr-only">Farm name</label>
                        <div class="relative">
                            <x-ui.icon name="house" class="{{ $iconInput }}" />
                            <input id="farm_name" type="text" wire:model="farm_name" @error('farm_name') aria-invalid="true" aria-describedby="farm_name-error" @enderror
                                   class="{{ $field('farm_name', 'pl-9') }}">
                        </div>
                        {!! $error('farm_name') !!}
                    </div>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Contact</p>
                        <p class="{{ $rowHelp }}">How the public is told to get in touch.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="d-fieldset-legend">Phone</label>
                            <div class="relative">
                                <x-ui.icon name="phone" class="{{ $iconInput }}" />
                                <input id="phone" type="tel" wire:model="phone" placeholder="+63 912 345 6789"
                                       @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                                       class="{{ $field('phone', 'datum pl-9') }}">
                            </div>
                            {!! $error('phone') !!}
                        </div>
                        <div>
                            <label for="email" class="d-fieldset-legend">Email</label>
                            <div class="relative">
                                <x-ui.icon name="mail" class="{{ $iconInput }}" />
                                <input id="email" type="email" wire:model="email" placeholder="farm@example.com"
                                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                       class="{{ $field('email', 'pl-9') }}">
                            </div>
                            {!! $error('email') !!}
                        </div>
                    </div>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Location and hours</p>
                        <p class="{{ $rowHelp }}">Where the farm is, and when visitors are welcome.</p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="address" class="d-fieldset-legend">Where to find us</label>
                            <div class="relative">
                                <x-ui.icon name="map-pin" class="{{ $iconInput }}" />
                                <input id="address" type="text" wire:model="address" placeholder="Barangay, town, province"
                                       @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                                       class="{{ $field('address', 'pl-9') }}">
                            </div>
                            {!! $error('address') !!}
                        </div>
                        <div>
                            <label for="hours" class="d-fieldset-legend">Visiting hours</label>
                            <div class="relative">
                                <x-ui.icon name="clock" class="{{ $iconInput }}" />
                                <input id="hours" type="text" wire:model="hours" placeholder="Mon to Sat, 7 AM to 5 PM"
                                       @error('hours') aria-invalid="true" aria-describedby="hours-error" @enderror
                                       class="{{ $field('hours', 'pl-9') }}">
                            </div>
                            {!! $error('hours') !!}
                        </div>
                    </div>
                </div>

                <div class="{{ $rowClass }}">
                    <div>
                        <p class="{{ $rowTitle }}">Note to visitors</p>
                        <p class="{{ $rowHelp }}">Optional &mdash; ring ahead on Sundays, where to park.</p>
                    </div>
                    <div>
                        <label for="visitor_note" class="sr-only">Note to visitors</label>
                        <textarea id="visitor_note" rows="3" wire:model="visitor_note"
                                  @error('visitor_note') aria-invalid="true" aria-describedby="visitor_note-error" @enderror
                                  @class(['d-textarea w-full', 'd-textarea-error' => $errors->has('visitor_note')])></textarea>
                        {!! $error('visitor_note') !!}
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-border bg-muted/40 px-5 py-3">
                    {{-- Quiet confirmation, gone at the next keystroke. --}}
                    @if ($saved)
                        <p class="mr-auto inline-flex items-center gap-1.5 text-[13px] text-muted-foreground lg:text-xs" x-show="! edited" wire:loading.remove wire:target="save">
                            <x-ui.icon name="circle-check" class="size-3.5 text-primary" /> All changes saved
                        </p>
                    @else
                        <p class="mr-auto text-[13px] text-muted-foreground lg:text-xs">Changes show on the public pages as soon as you save.</p>
                    @endif
                    <x-ui.button type="submit">Save Changes</x-ui.button>
                </div>
            </x-ui.card>

            <div class="flex flex-col gap-4">
                {{-- What the public sees, drawn like the public contact card and
                     following the fields as they are typed. --}}
                <x-ui.card :flush="true" aria-labelledby="preview-heading">
                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <h2 id="preview-heading" class="text-sm font-semibold text-foreground">Public Page Preview</h2>
                        <span class="d-badge d-badge-soft d-badge-success">Live</span>
                    </div>
                    <div class="mx-5 mb-5 overflow-hidden rounded-md border border-border">
                        <div class="flex items-center gap-3 bg-muted/60 px-4 py-3">
                            <x-brand-mark :size="28" class="rounded-full" />
                            <p class="min-w-0 truncate text-sm font-semibold text-foreground" x-text="$wire.farm_name || 'Unnamed farm'">{{ $farm_name ?: 'Unnamed farm' }}</p>
                        </div>
                        <ul class="space-y-3 px-4 py-4">
                            @foreach ([
                                ['phone', 'phone', 'Phone', true],
                                ['email', 'mail', 'Email', false],
                                ['address', 'map-pin', 'Where to find us', false],
                                ['hours', 'clock', 'Visiting hours', false],
                            ] as [$key, $icon, $label, $tabular])
                                <li class="flex items-start gap-3" x-show="$wire.{{ $key }}" @unless ($$key) x-cloak @endunless>
                                    <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <x-ui.icon :name="$icon" class="size-3.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-medium text-muted-foreground">{{ $label }}</span>
                                        <span @class(['block break-words text-[13px] text-foreground lg:text-xs', 'datum' => $tabular]) x-text="$wire.{{ $key }}">{{ $$key }}</span>
                                    </span>
                                </li>
                            @endforeach
                            <li class="text-[13px] leading-relaxed text-muted-foreground lg:text-xs"
                                x-show="! ($wire.phone || $wire.email || $wire.address || $wire.hours)"
                                @if ($phone || $email || $address || $hours) x-cloak @endif>
                                With all four blank the front page shows no way to contact the farm at all.
                            </li>
                        </ul>
                        <div class="flex items-start gap-2 border-t border-border bg-muted/40 px-4 py-3 text-[13px] leading-relaxed text-foreground lg:text-xs"
                             x-show="$wire.visitor_note" @unless ($visitor_note) x-cloak @endunless>
                            <x-ui.icon name="message-square" class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                            <span x-text="$wire.visitor_note">{{ $visitor_note }}</span>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <x-ui.card.header>
                        <x-ui.card.title>Where It Appears</x-ui.card.title>
                    </x-ui.card.header>
                    <x-ui.card.content>
                        <ul class="space-y-3 text-[13px] lg:text-xs">
                            @foreach ([
                                ['house', 'Front page', 'The Visit Us section'],
                                ['layout-grid', 'Catalogue', 'The footer of every catalogue page'],
                                ['file-text', 'Reports', 'The footer of every PDF report'],
                            ] as [$icon, $place, $where])
                                <li class="flex items-start gap-3">
                                    <span class="inline-flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                        <x-ui.icon :name="$icon" class="size-3.5" />
                                    </span>
                                    <span>
                                        <span class="block font-medium text-foreground">{{ $place }}</span>
                                        <span class="block text-muted-foreground">{{ $where }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card.content>
                </x-ui.card>
            </div>
        </div>
    </form>
</div>
