{{-- Shown while the application is deliberately down - a deploy, or a migration
     being run by hand. Distinct from 500 on purpose: nothing is broken, and
     saying "we are working on it" is true here and a guess there. --}}
<x-error-page code="503" heading="The records are closed for a moment" title="Back shortly">
    <p>
        The system is being updated. This is planned, and it is usually over within
        a few minutes.
    </p>
    <p>
        Nothing that was already saved is affected. Try again shortly.
    </p>
</x-error-page>
