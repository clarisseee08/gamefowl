{{-- The plain-text version, for mail apps that do not show HTML. Same words, same link. --}}
{!! $farm['name'] !!}

Reset your password

We received a request to reset the password for {!! $email !!}. If that was you, choose a new one by opening this link:

{!! $url !!}

This link works for {{ $minutes }} minutes, and only once.

Didn't ask for this? You can ignore this email. Your password stays the same unless you open the link and save a new one.

--
{!! $farm['name'] !!}@if (filled($farm['address'])) · {!! $farm['address'] !!}@endif

You're getting this because someone asked to reset the password for your account on the farm's record system.
