{{--
    The catalogue on its own page.

    Everything here is the nested component: the grid, the filters and the
    pagination live in Catalog\Browse. What this file owns is the page - the
    shell chosen by who is looking, and the title. See App\Livewire\Catalog\Index.

    THE PAGE MEASURE IS PUBLIC-ONLY. A visitor sees this inside the stud-book
    shell, which leaves margins and section spacing to the page; staff see it
    inside the console, which already pads its main region and must not gain
    a centred column inside it (DesignSystemGuardTest holds that rule).
--}}
@php $public = ! (auth()->user()?->isInternal() ?? false); @endphp

<div @class(['sb-wrap pb-16 pt-10 lg:pb-28 lg:pt-14' => $public])>
    <livewire:catalog.browse />
</div>
