<x-layouts::guest title="Reset your password">
    <h1 class="rise-in text-2xl font-bold tracking-tight text-foreground"
        style="--rise-delay: 180ms">Forgot your password?</h1>
    <p class="rise-in mt-1.5 text-[15px] leading-relaxed text-muted-foreground lg:text-sm" style="--rise-delay: 240ms">
        Enter your email address and we will send you a link to choose a new password.
    </p>

    @if (session('status'))
        <p class="d-alert d-alert-soft d-alert-success rise-in mt-6 text-[15px] text-base-content" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="email" class="d-fieldset-legend">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" inputmode="email"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="d-input w-full @error('email') d-input-error @enderror">
            @error('email')
                <p id="email-error" class="mt-1.5 text-[13px] font-medium text-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="d-btn d-btn-primary rise-in w-full" style="--rise-delay: 360ms">Email password reset link</button>
    </form>

    <a href="{{ route('login') }}" wire:navigate
       class="d-link d-link-primary rise-in mt-6 inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold"
       style="--rise-delay: 420ms">
        <span aria-hidden="true">&larr;</span> Back to sign in
    </a>
</x-layouts::guest>
