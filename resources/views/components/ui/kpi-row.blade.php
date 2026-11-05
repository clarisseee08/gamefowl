{{--
    A ROW OF KPI CARDS (EMS, Part B §6.6): one column on a phone, two from sm,
    four from lg, above the screen's main surface.
--}}
<div {{ $attributes->class('grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4') }}>
    {{ $slot }}
</div>
