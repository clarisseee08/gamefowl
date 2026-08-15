<x-layouts::guest title="Choose a new password">
    <h2 class="text-lg font-semibold text-gray-900">Choose a new password</h2>
    <p class="mt-1 text-sm text-gray-600">
        Your new password must be at least 8 characters long.
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

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="label">Email Address</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   class="input mt-1 @error('email') input-error @enderror">
        </div>

        <div>
            <label for="password" class="label">New Password</label>
            <input id="password" type="password" name="password" required
                   autocomplete="new-password"
                   class="input mt-1 @error('password') input-error @enderror">
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm New Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password" class="input mt-1">
        </div>

        <button type="submit" class="btn-primary w-full">Save new password</button>
    </form>
</x-layouts::guest>
