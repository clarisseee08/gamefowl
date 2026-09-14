{{-- This one must assume the database is the thing that failed, so it says
     nothing that would need a query to be true. The component already holds to
     that rule; this page simply does not add a claim about what was saved,
     because it cannot know. --}}
<x-error-page code="500" heading="Something broke at our end" title="Something went wrong">
    <p>
        This is a fault in the system rather than anything you did. It has been
        recorded in the farm's log.
    </p>
    <p>
        Try again in a moment. If it keeps happening, tell whoever maintains this
        and give them the number above &mdash; it is what points them at the right
        entry in the log.
    </p>
</x-error-page>
