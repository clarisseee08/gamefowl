<x-layouts::app title="Dashboard">
    <div class="mb-12">
        <h1 class="text-[34px] font-semibold tracking-[-0.022em] leading-[1.12] text-ink">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
            {{ Str::before($user->full_name, ' ') }}
        </h1>
        <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
            Here is how {{ config('gfms.farm.name') }} is doing today.
            You are signed in as <strong>{{ $user->role->label() }}</strong>.
        </p>
    </div>

    <livewire:dashboard.overview />
</x-layouts::app>
