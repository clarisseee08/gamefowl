@php
    /*
     * Deliberately plain. This page is read under pressure, usually with
     * something broken, so every row is label + value and the only colour is
     * the pass/fail state.
     */
    $state = fn (bool $ok) => $ok ? 'badge badge-ok' : 'badge badge-alert';

    // The headline is counted from the same arrays the rows below render -
    // nothing is probed twice and nothing is invented.
    $failing = count(array_filter($extensions, fn ($loaded) => ! $loaded))
        + count(array_filter($paths, fn ($info) => ! $info['exists'] || ! $info['writable']))
        + count(array_filter($disks, fn ($result) => $result !== 'reachable'))
        + count(array_filter($services, fn ($value) => $value === 'MISSING'));

    $headingClass = 'font-display text-[19px] font-semibold tracking-[-0.015em] text-foreground';
    $noteClass = 'mt-1 max-w-[60ch] text-[14px] leading-relaxed text-muted-foreground';
    $rowClass = 'flex min-h-11 items-center justify-between gap-4 border-b border-border py-2.5';
@endphp

<x-layouts::app title="Diagnostics">
    <x-anchor-block eyebrow="Owner only" title="Diagnostics"
                    :figure="$failing" :figure-label="$failing === 1 ? 'check needs attention' : 'checks need attention'">
        <span class="block max-w-[70ch] leading-relaxed">
            What this server can actually do, as opposed to what it is configured to do.
            Render's free plan has no shell, so this page is the only way to ask the running
            container anything. Credentials are reported as present or missing and never shown.
        </span>
    </x-anchor-block>

    <div class="mt-10 grid gap-x-14 gap-y-12 lg:grid-cols-2">

        {{-- Extensions first: a missing one is the most common cause of a 500
             that appears only in production. --}}
        <section aria-labelledby="diag-extensions">
            <h2 id="diag-extensions" class="{{ $headingClass }}">PHP extensions</h2>
            <p class="{{ $noteClass }}">
                <code class="datum">fileinfo</code> is the first thing to check for a failing upload &mdash;
                Livewire reads the MIME type of every temporary file, and with no guesser
                available it throws rather than returning null.
            </p>
            <dl class="mt-4 border-t border-border">
                @foreach ($extensions as $name => $loaded)
                    <div class="{{ $rowClass }}">
                        <dt class="datum text-[15px] text-foreground">{{ $name }}</dt>
                        <dd><span class="{{ $state($loaded) }}">{{ $loaded ? 'loaded' : 'MISSING' }}</span></dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="diag-uploads">
            <h2 id="diag-uploads" class="{{ $headingClass }}">Uploads</h2>
            <dl class="mt-4 border-t border-border">
                @foreach ($uploads as $label => $value)
                    <div class="{{ $rowClass }}">
                        <dt class="text-[15px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="datum text-right text-[15px] text-foreground">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="diag-paths">
            <h2 id="diag-paths" class="{{ $headingClass }}">Writable paths</h2>
            <p class="{{ $noteClass }}">
                A container filesystem that looked fine when the image was built can still be
                unwritable to the php-fpm user.
            </p>
            <dl class="mt-4 border-t border-border">
                @foreach ($paths as $label => $info)
                    <div class="{{ $rowClass }}">
                        <dt class="datum min-w-0 break-all text-[14px] text-foreground">{{ $label }}</dt>
                        <dd class="flex shrink-0 gap-1.5">
                            <span class="{{ $state($info['exists']) }}">{{ $info['exists'] ? 'exists' : 'missing' }}</span>
                            <span class="{{ $state($info['writable']) }}">{{ $info['writable'] ? 'writable' : 'read-only' }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="diag-disks">
            <h2 id="diag-disks" class="{{ $headingClass }}">Storage disks</h2>
            <p class="{{ $noteClass }}">
                Probed with a harmless existence check, which proves the credentials and the
                network path without writing anything or listing any files.
            </p>
            <dl class="mt-4 border-t border-border">
                @foreach ($disks as $name => $result)
                    <div class="{{ $rowClass }}">
                        <dt class="datum text-[15px] text-foreground">{{ $name }}</dt>
                        <dd class="text-right">
                            @if ($result === 'reachable')
                                <span class="badge badge-ok">{{ $result }}</span>
                            @else
                                <span class="text-[14px] text-destructive">{{ $result }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="diag-services">
            <h2 id="diag-services" class="{{ $headingClass }}">Services</h2>
            <dl class="mt-4 border-t border-border">
                @foreach ($services as $label => $value)
                    <div class="{{ $rowClass }}">
                        <dt class="text-[15px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="text-right">
                            @if ($value === 'MISSING')
                                <span class="badge badge-alert">{{ $value }}</span>
                            @else
                                <span class="datum text-[15px] text-foreground">{{ $value }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section aria-labelledby="diag-runtime">
            <h2 id="diag-runtime" class="{{ $headingClass }}">Runtime</h2>
            <dl class="mt-4 border-t border-border">
                @foreach ($php as $label => $value)
                    <div class="{{ $rowClass }}">
                        <dt class="text-[15px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="datum text-right text-[15px] text-foreground">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-layouts::app>
