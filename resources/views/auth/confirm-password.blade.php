<x-layouts::guest title="Confirm your password">
    <h1 class="rise-in text-2xl font-bold tracking-tight text-foreground"
        style="--rise-delay: 180ms">Confirm your password</h1>
    <p class="rise-in mt-1.5 text-[15px] leading-relaxed text-muted-foreground lg:text-sm" style="--rise-delay: 240ms">
        This is a secure area. Please enter your password again to continue.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="password" class="d-fieldset-legend">Password</label>
            <div class="relative">
                <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                       class="d-input w-full pr-12 @error('password') d-input-error @enderror">
                <x-password-reveal for="password" :compact="false" />
            </div>
            @error('password')
                <p id="password-error" class="mt-1.5 text-[13px] font-medium text-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="d-btn d-btn-primary rise-in w-full" style="--rise-delay: 360ms">Confirm</button>
    </form>
</x-layouts::guest>
