<x-layouts::guest title="Sign in">
    <h2 class="text-center text-[24px] font-semibold tracking-[-0.015em] text-ink">Sign in to your account</h2>
    <p class="mt-3 text-center text-[17px] leading-relaxed text-ink-48">
        Enter the email address and password given to you by the farm owner.
    </p>

    {{-- Status message, e.g. after a successful password reset. --}}
    @if (session('status'))
        <div class="mt-4 rounded-lg bg-ok-wash p-3 text-sm text-ok ring-1 ring-ok/20">
            {{ session('status') }}
        </div>
    @endif

    {{-- Fortify throws a validation error for wrong credentials, deactivated
         accounts and rate limiting. Show them together, in plain language. --}}
    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-alert-wash p-3 ring-1 ring-alert/20" role="alert">
            <ul class="space-y-1 text-sm text-alert">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
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
            <label for="remember" class="flex items-center gap-2 text-sm text-ink-80">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-hairline text-action focus:ring-action"
                >
                Keep me signed in
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-action hover:underline">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>
</x-layouts::guest>
