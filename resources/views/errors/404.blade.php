{{-- The one a customer is most likely to meet: a stale link to a bird that has
     been sold, or a mistyped band number in the address bar. --}}
<x-error-page code="404" heading="That page is not here" title="Page not found">
    <p>
        The link may be out of date, or the record it pointed at may have been
        removed from the farm's active list.
    </p>
    <p>
        If you followed a link to a particular bird, it may have been sold or is no
        longer listed. The catalogue shows everything currently on the farm.
    </p>

    <x-slot:actions>
        <a href="{{ route('catalog.index') }}" class="btn-secondary">Browse the catalogue</a>
    </x-slot:actions>
</x-error-page>
