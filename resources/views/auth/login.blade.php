<x-layouts::guest title="Sign in">
    {{-- The staged arrival continues from the brand panel across to the form.
         It is one sequence, about a second end to end, and it runs once on
         load. The delays live in a custom property so the order is readable
         down the file instead of hidden in six animation shorthands. --}}
    <h2 class="rise-in text-center text-[24px] font-semibold tracking-[-0.015em] text-foreground"
        style="--rise-delay: 300ms">Sign in to your account</h2>
    <p class="rise-in mt-3 text-center text-[17px] leading-relaxed text-muted-foreground"
       style="--rise-delay: 360ms">
        Enter the email address and password given to you by the farm owner.
    </p>

    {{-- Status message, e.g. after a successful password reset. --}}
    @if (session('status'))
        <div class="rise-in mt-4 rounded-lg bg-success-bg p-3 text-sm text-foreground ring-1 ring-success/20"
             style="--rise-delay: 140ms">
            {{ session('status') }}
        </div>
    @endif

    {{-- Fortify throws a validation error for wrong credentials, deactivated
         accounts and rate limiting. Show them together, in plain language.

         FIRST IN THE SEQUENCE, not last. Someone reading this has already seen
         this page once and come back to fix something; making them wait out an
         entrance to find out what went wrong would be the animation working
         against the person using it. --}}
    @if ($errors->any())
        <div id="login-error" class="rise-in mt-4 rounded-lg bg-destructive-bg p-3 ring-1 ring-destructive/20"
             style="--rise-delay: 120ms" role="alert">
            <ul class="space-y-1 text-sm text-foreground">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        {{-- ---------------------------------------------------------------
             WHY THE LABEL CARRIES THE ERROR.

             The focus ring is --ring-color, primary at 35%. The error ring is
             --ring-color-destructive, destructive at 25%. Both are red, both
             are three pixels, and the email field is autofocus - so the very
             first thing anyone saw on a clean load was a field that looked
             like it had already been rejected.

             Fixing that properly means deciding what colour focus should be
             across the whole application, which is a token decision and not
             one to take in passing. What this does instead is give the error
             its own channel: the LABEL turns, the ring does not. A red ring is
             now where you are; a red label is what is wrong.
        --------------------------------------------------------------- --}}
        <div class="rise-in" style="--rise-delay: 420ms">
            <label for="email" @class(['label', 'text-destructive' => $errors->has('email')])>Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                inputmode="email"
                @if ($errors->has('email')) aria-invalid="true" aria-describedby="login-error" @endif
                class="input mt-1 @error('email') input-error @enderror"
            >
        </div>

        {{-- ---------------------------------------------------------------
             SHOW / HIDE, because this is typed one-handed outdoors.

             A real <button type="button">: a bare <button> inside a form
             submits it, and a div with a click handler cannot be reached by
             keyboard. aria-pressed carries the state and the label says what
             the button will DO next, not what it is showing now.

             The icon is authored SVG at the same 1.6 stroke as every other
             icon in this application. An emoji eye is not an icon system.

             Masked by default and it stays masked until asked - the point of
             the control is that revealing is a decision, in a yard where
             someone else can see the screen.
        --------------------------------------------------------------- --}}
        <div class="rise-in" style="--rise-delay: 480ms">
            <label for="password" @class(['label', 'text-destructive' => $errors->has('password')])>Password</label>

            <div class="relative mt-1">
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    @if ($errors->has('password')) aria-invalid="true" aria-describedby="login-error" @endif
                    class="input pr-12 @error('password') input-error @enderror"
                >

                <button
                    type="button"
                    class="password-reveal"
                    data-reveals="password"
                    aria-controls="password"
                    aria-pressed="false"
                    aria-label="Show password"
                >
                    <svg data-icon="show" class="h-5 w-5" fill="none" stroke="currentColor"
                         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>

                    <svg data-icon="hide" hidden class="h-5 w-5" fill="none" stroke="currentColor"
                         stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243"/>
                    </svg>
                </button>
            </div>

            {{-- Caps Lock. A masked field is the one place a stuck Caps Lock
                 costs a real attempt, and on this installation a wrong attempt
                 counts against Fortify's rate limiter.

                 role=status so it is announced rather than only drawn, and it
                 lives in the DOM from the start - a live region inserted at the
                 moment it has something to say is frequently not announced at
                 all. --}}
            <p data-capslock-for="password" hidden role="status"
               class="mt-2 flex items-center gap-1.5 text-[13px] text-warning">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor"
                     stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                Caps Lock is on.
            </p>
        </div>

        <div class="rise-in flex items-center justify-between" style="--rise-delay: 540ms">
            <label for="remember" class="flex items-center gap-2 text-sm text-muted-foreground">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                >
                Keep me signed in
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-primary rise-in w-full" style="--rise-delay: 600ms">Sign in</button>
    </form>
</x-layouts::guest>
