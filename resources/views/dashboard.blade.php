<x-layouts::app title="Dashboard">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
            {{ Str::before($user->full_name, ' ') }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            Here is how {{ config('gfms.farm.name') }} is doing today.
            You are signed in as <strong>{{ $user->role->label() }}</strong>.
        </p>
    </div>

    <livewire:dashboard.overview />
</x-layouts::app>
