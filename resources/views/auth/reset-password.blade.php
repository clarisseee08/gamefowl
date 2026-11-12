<x-layouts::guest title="Choose a new password">
    <h1 class="rise-in text-2xl font-bold tracking-tight text-foreground"
        style="--rise-delay: 180ms">Choose a new password</h1>
    <p class="rise-in mt-1.5 text-[15px] leading-relaxed text-muted-foreground lg:text-sm" style="--rise-delay: 240ms">
        Your new password must be at least 8 characters long.
    </p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="rise-in" style="--rise-delay: 300ms">
            <label for="email" class="d-fieldset-legend">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autofocus autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                   class="d-input w-full @error('email') d-input-error @enderror">
            @error('email')
                <p id="email-error" class="mt-1.5 text-[13px] font-medium text-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="rise-in" style="--rise-delay: 360ms">
            <label for="password" class="d-fieldset-legend">New password</label>
            <div class="relative">
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       aria-describedby="password-help @error('password') password-error @enderror"
                       @error('password') aria-invalid="true" @enderror
                       class="d-input w-full pr-12 @error('password') d-input-error @enderror">
                <x-password-reveal for="password" :compact="false" />
            </div>
            <p id="password-help" class="d-label mt-1">At least 8 characters.</p>
            @error('password')
                <p id="password-error" class="mt-1.5 text-[13px] font-medium text-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="rise-in" style="--rise-delay: 420ms">
            <label for="password_confirmation" class="d-fieldset-legend">Confirm new password</label>
            <div class="relative">
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required autocomplete="new-password" class="d-input w-full pr-12">
                <x-password-reveal for="password_confirmation" :compact="false" />
            </div>
        </div>

        <button type="submit" class="d-btn d-btn-primary rise-in w-full" style="--rise-delay: 480ms">Save new password</button>
    </form>
</x-layouts::guest>
