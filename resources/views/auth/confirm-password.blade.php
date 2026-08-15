<x-layouts::guest title="Confirm your password">
    <h2 class="text-[24px] font-semibold tracking-[-0.015em] leading-[1.2] text-ink">Please confirm your password</h2>
    <p class="mt-3 text-[17px] leading-relaxed text-ink-48">
        This is a secure area. Please enter your password again to continue.
    </p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-alert-wash p-3 ring-1 ring-alert/20" role="alert">
            <ul class="space-y-1 text-sm text-alert">
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
