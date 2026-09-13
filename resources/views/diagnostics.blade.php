@php
    /*
     * Deliberately plain. This page is read under pressure, usually with
     * something broken, so every row is label + value and the only colour is
     * the pass/fail state.
     */
    $state = fn (bool $ok) => $ok ? 'badge badge-ok' : 'badge badge-alert';
@endphp

<x-layouts::app title="Diagnostics">
    <div class="mb-8">
        <h1 class="page-title-marked text-[26px] font-semibold leading-[1.2] text-foreground">Diagnostics</h1>
        <p class="mt-2 max-w-[70ch] text-[15px] leading-relaxed text-muted-foreground">
            What this server can actually do, as opposed to what it is configured to do.
            Render's free plan has no shell, so this page is the only way to ask the running
            container anything. Credentials are reported as present or missing and never shown.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Extensions first: a missing one is the most common cause of a 500
             that appears only in production. --}}
        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">PHP extensions</h2>
            <p class="mt-1 text-[13px] text-muted-foreground">
                <code class="datum">fileinfo</code> is the first thing to check for a failing upload &mdash;
                Livewire reads the MIME type of every temporary file, and with no guesser
                available it throws rather than returning null.
            </p>
            <dl class="mt-4 space-y-2">
                @foreach ($extensions as $name => $loaded)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="datum text-[14px] text-foreground">{{ $name }}</dt>
                        <dd><span class="{{ $state($loaded) }}">{{ $loaded ? 'loaded' : 'MISSING' }}</span></dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">Uploads</h2>
            <dl class="mt-4 space-y-2">
                @foreach ($uploads as $label => $value)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-[14px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="datum text-right text-[14px] text-foreground">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">Writable paths</h2>
            <p class="mt-1 text-[13px] text-muted-foreground">
                A container filesystem that looked fine when the image was built can still be
                unwritable to the php-fpm user.
            </p>
            <dl class="mt-4 space-y-2">
                @foreach ($paths as $label => $info)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="datum text-[13px] text-foreground">{{ $label }}</dt>
                        <dd class="flex gap-1.5">
                            <span class="{{ $state($info['exists']) }}">{{ $info['exists'] ? 'exists' : 'missing' }}</span>
                            <span class="{{ $state($info['writable']) }}">{{ $info['writable'] ? 'writable' : 'read-only' }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">Storage disks</h2>
            <p class="mt-1 text-[13px] text-muted-foreground">
                Probed with a harmless existence check, which proves the credentials and the
                network path without writing anything or listing any files.
            </p>
            <dl class="mt-4 space-y-2">
                @foreach ($disks as $name => $result)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="datum text-[14px] text-foreground">{{ $name }}</dt>
                        <dd class="text-right text-[13px] {{ $result === 'reachable' ? 'text-success' : 'text-destructive' }}">
                            {{ $result }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">Services</h2>
            <dl class="mt-4 space-y-2">
                @foreach ($services as $label => $value)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-[14px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="datum text-right text-[14px] {{ $value === 'MISSING' ? 'text-destructive' : 'text-foreground' }}">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="text-[17px] font-semibold text-foreground">Runtime</h2>
            <dl class="mt-4 space-y-2">
                @foreach ($php as $label => $value)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-[14px] text-muted-foreground">{{ $label }}</dt>
                        <dd class="datum text-right text-[14px] text-foreground">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-layouts::app>
