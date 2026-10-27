<x-layouts::guest title="Reset your password">
    <h1 class="rise-in font-display text-[2rem] font-semibold leading-[1.1] tracking-[-0.03em] text-foreground sm:text-[2.4rem]"
        style="--rise-delay: 180ms">Forgot your password?</h1>
    <p class="rise-in mt-3 text-[16px] leading-relaxed text-muted-foreground" style="--rise-delay: 240ms">
        Enter your email address and we will send you a link to choose a new password.
    </p>

    @if (session('status'))
        <p class="rise-in mt-6 rounded-lg bg-success-bg px-4 py-3 text-[15px] text-foreground" role="status">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-9 space-y-6">
        @csrf

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="email" class="label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" inputmode="email"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p id="email-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary rise-in w-full" style="--rise-delay: 360ms">Email password reset link</button>
    </form>

    <a href="{{ route('login') }}" wire:navigate
       class="rise-in mt-6 inline-flex min-h-11 items-center gap-1.5 text-[15px] font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary"
       style="--rise-delay: 420ms">
        <span aria-hidden="true">&larr;</span> Back to sign in
    </a>
</x-layouts::guest>
