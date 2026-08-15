<x-layouts::guest title="Sign in">
    <h2 class="text-lg font-semibold text-gray-900">Sign in to your account</h2>
    <p class="mt-1 text-sm text-gray-600">
        Enter the email address and password given to you by the farm owner.
    </p>

    {{-- Status message, e.g. after a successful password reset. --}}
    @if (session('status'))
        <div class="mt-4 rounded-lg bg-brand-50 p-3 text-sm text-brand-800 ring-1 ring-brand-200">
            {{ session('status') }}
        </div>
    @endif

    {{-- Fortify throws a validation error for wrong credentials, deactivated
         accounts and rate limiting. Show them together, in plain language. --}}
    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-rose-50 p-3 ring-1 ring-rose-200" role="alert">
            <ul class="space-y-1 text-sm text-rose-700">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                inputmode="email"
                class="input mt-1 @error('email') input-error @enderror"
            >
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="input mt-1 @error('password') input-error @enderror"
            >
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center gap-2 text-sm text-gray-700">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                >
                Keep me signed in
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>
</x-layouts::guest>
