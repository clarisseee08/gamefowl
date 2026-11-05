@props([
    /** No longer shown: EMS copy rules have no eyebrow labels. Accepted so call sites keep working. */
    'eyebrow' => null,
    'title',
    'figure' => null,
    'figureLabel' => null,
])

{{--
    KEPT AS AN ALIAS while each screen moves to <x-ui.page-header>: every
    console page still opens through here, and this now renders the EMS page
    header - a 16px bold title, the page's figure as one small line under
    it, the actions on the right. See components/ui/page-header for why.
--}}
<x-ui.page-header :title="$title" :figure="$figure" :figure-label="$figureLabel" {{ $attributes }}>
    {{ $slot }}

    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
</x-ui.page-header>
