<x-layouts::guest title="Confirm your password">
    <h2 class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-foreground">Please confirm your password</h2>
    <p class="mt-3 text-[17px] leading-relaxed text-muted-foreground">
        This is a secure area. Please enter your password again to continue.
    </p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-destructive-bg p-3 ring-1 ring-destructive/20" role="alert">
            <ul class="space-y-1 text-sm text-destructive">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="password" class="label">Password</label>
            <input id="password" type="password" name="password" required autofocus
                   autocomplete="current-password"
                   class="input mt-1 @error('password') input-error @enderror">
        </div>

        <button type="submit" class="btn-primary w-full">Confirm</button>
    </form>
</x-layouts::guest>
