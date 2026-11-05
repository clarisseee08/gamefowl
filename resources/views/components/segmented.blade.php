@props([
    /** @var list<array{key:string,label:string,count?:int|null}> */
    'segments' => [],
    'active' => null,
    'model' => 'tab',
    'label' => null,
])

{{--
    KEPT AS AN ALIAS while screens move to <x-ui.tabs>: renders the EMS
    segmented pill, which switches the instant a tab is clicked and lets the
    list catch up behind it.
--}}
<x-ui.tabs variant="segmented" :segments="$segments" :active="$active" :model="$model" :label="$label" {{ $attributes }} />
