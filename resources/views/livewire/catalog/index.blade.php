{{--
    The catalogue on its own page.

    Everything here is the nested component: the grid, the filters and the
    pagination live in Catalog\Browse so that this page and the landing page
    show the same catalogue rather than two that drift apart.

    What this file owns is the page - the shell chosen by who is looking, and
    the title. See App\Livewire\Catalog\Index.
--}}
<div>
    <livewire:catalog.browse />
</div>
