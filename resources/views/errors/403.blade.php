{{-- Not "Forbidden". The person is usually signed in and simply does not hold
     the role this screen needs - staff reaching an owner-only page, or a
     customer following a link into the console. Saying so is more useful than
     the protocol's word for it. --}}
<x-error-page code="403" heading="That one is not yours to open" title="Not allowed">
    <p>
        Your account does not have access to this page. Some screens are limited to
        farm staff, and a few to the owner alone.
    </p>
    <p>
        If you think you should be able to see it, the farm owner can change what
        your account is allowed to do.
    </p>
</x-error-page>
