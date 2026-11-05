@props([
    /** Pedigree::generations - column 0 is the bird, column 1 its sire and dam, and so on. */
    'generations',
    'column' => 0,
    'index' => 0,
])

{{--
    ONE BIRD AND EVERYTHING ABOVE IT, drawn as a family tree: the parents
    side by side above the bird, joined to it by the connector lines in
    app.css (.ft-*). Recursive - each parent draws its own parents - so the
    tree is as deep as Pedigree::generations is, with no column count in the
    markup. A parent that is not recorded is drawn as an empty card and has
    nothing above it.
--}}
@php
    $bird = $generations[$column][$index] ?? null;
    $hasParents = $bird !== null && isset($generations[$column + 1]);

    $role = match ($column) {
        0 => 'This Bird',
        1 => $index % 2 === 0 ? 'Sire' : 'Dam',
        2 => $index % 2 === 0 ? 'Grandsire' : 'Granddam',
        default => $index % 2 === 0 ? 'Ancestor (sire)' : 'Ancestor (dam)',
    };
@endphp

<div class="flex flex-col items-center">
    @if ($hasParents)
        <div class="ft-parents">
            <div class="ft-branch">
                <x-family-tree.branch :generations="$generations" :column="$column + 1" :index="$index * 2" />
            </div>
            <div class="ft-branch">
                <x-family-tree.branch :generations="$generations" :column="$column + 1" :index="$index * 2 + 1" />
            </div>
        </div>
    @endif

    <div @class(['ft-child' => $hasParents])>
        <x-family-tree.node :bird="$bird" :role="$role" :root="$column === 0" />
    </div>
</div>
