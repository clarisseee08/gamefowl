<x-layouts::app title="Dashboard">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            Welcome, {{ $user->full_name }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
            You are signed in as <strong>{{ $user->role->label() }}</strong>.
            {{ $user->role->description() }}
        </p>
    </div>

    @if ($totalBroodcocks !== null)
        <div class="card p-6">
            <p class="text-sm font-medium text-gray-600">Total Broodcocks Recorded</p>
            <p class="mt-2 text-4xl font-bold text-gray-900">{{ number_format($totalBroodcocks) }}</p>
        </div>
    @endif
</x-layouts::app>
