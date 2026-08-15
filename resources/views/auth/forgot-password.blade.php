<x-layouts::guest title="Reset your password">
    <h2 class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-ink">Forgot your password?</h2>
    <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
        Enter your email address and we will send you a link to choose a new password.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-ok-wash p-3 text-sm text-ok ring-1 ring-ok/20">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-alert-wash p-3 ring-1 ring-alert/20" role="alert">
            <ul class="space-y-1 text-sm text-alert">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" inputmode="email"
                   class="input mt-1 @error('email') input-error @enderror">
        </div>

        <button type="submit" class="btn-primary w-full">Email password reset link</button>

        <a href="{{ route('login') }}" class="block text-center text-sm font-medium text-action hover:underline">
            Back to sign in
        </a>
    </form>
</x-layouts::guest>
