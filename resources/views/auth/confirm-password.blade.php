<x-layouts::guest title="Confirm your password">
    <h1 class="rise-in font-display text-[2rem] font-semibold leading-[1.1] tracking-[-0.03em] text-foreground sm:text-[2.4rem]"
        style="--rise-delay: 180ms">Confirm your password</h1>
    <p class="rise-in mt-3 text-[16px] leading-relaxed text-muted-foreground" style="--rise-delay: 240ms">
        This is a secure area. Please enter your password again to continue.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-9 space-y-6">
        @csrf

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="password" class="label">Password</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p id="password-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary rise-in w-full" style="--rise-delay: 360ms">Confirm</button>
    </form>
</x-layouts::guest>
