<x-layouts::guest title="Reset your password">
    <h2 class="text-lg font-semibold text-gray-900">Forgot your password?</h2>
    <p class="mt-1 text-sm text-gray-600">
        Enter your email address and we will send you a link to choose a new password.
    </p>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-brand-50 p-3 text-sm text-brand-800 ring-1 ring-brand-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-rose-50 p-3 ring-1 ring-rose-200" role="alert">
            <ul class="space-y-1 text-sm text-rose-700">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autofocus autocomplete="username" inputmode="email"
                   class="input mt-1 @error('email') input-error @enderror">
        </div>

        <button type="submit" class="btn-primary w-full">Email password reset link</button>

        <a href="{{ route('login') }}" class="block text-center text-sm font-medium text-brand-700 hover:text-brand-800">
            Back to sign in
        </a>
    </form>
</x-layouts::guest>
