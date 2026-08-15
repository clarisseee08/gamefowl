<x-layouts::guest title="Confirm your password">
    <h2 class="text-lg font-semibold text-gray-900">Please confirm your password</h2>
    <p class="mt-1 text-sm text-gray-600">
        This is a secure area. Please enter your password again to continue.
    </p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-rose-50 p-3 ring-1 ring-rose-200" role="alert">
            <ul class="space-y-1 text-sm text-rose-700">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" type="password" name="password" required autofocus
                   autocomplete="current-password"
                   class="input mt-1 @error('password') input-error @enderror">
        </div>

        <button type="submit" class="btn-primary w-full">Confirm</button>
    </form>
</x-layouts::guest>
