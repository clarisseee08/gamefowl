{{-- The one that confuses people most, because nothing is broken and the word
     "expired" sounds like something was lost. It happens when a form sat open
     long enough for the session token to lapse - so the copy leads with the fix
     and with the fact that the entry is still in the browser. --}}
<x-error-page code="419" heading="This page sat open too long" title="Page expired">
    <p>
        For security, a form stops being accepted after a while. Nothing is wrong
        and nothing is broken.
    </p>
    <p>
        Go back and reload the page, then submit it again. Anything you typed is
        usually still in the form when you go back.
    </p>

    <x-slot:actions>
        <button type="button" onclick="history.back()" class="btn-secondary">Go back</button>
    </x-slot:actions>
</x-error-page>
