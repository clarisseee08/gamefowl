<x-layouts::guest title="Choose a new password">
    <h1 class="rise-in font-display text-[2rem] font-semibold leading-[1.1] tracking-[-0.03em] text-foreground sm:text-[2.4rem]"
        style="--rise-delay: 180ms">Choose a new password</h1>
    <p class="rise-in mt-3 text-[16px] leading-relaxed text-muted-foreground" style="--rise-delay: 240ms">
        Your new password must be at least 8 characters long.
    </p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-9 space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="email" class="label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p id="email-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="rise-in" style="--rise-delay: 360ms">
            <label for="password" class="label">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   aria-describedby="password-help @error('password') password-error @enderror"
                   @error('password') aria-invalid="true" @enderror
                   class="input @error('password') input-error @enderror">
            <p id="password-help" class="help">At least 8 characters.</p>
            @error('password')
                <p id="password-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="rise-in" style="--rise-delay: 420ms">
            <label for="password_confirmation" class="label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   required autocomplete="new-password" class="input">
        </div>

        <button type="submit" class="btn-primary rise-in w-full" style="--rise-delay: 480ms">Save new password</button>
    </form>
</x-layouts::guest>
