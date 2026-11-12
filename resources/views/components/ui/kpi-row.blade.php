{{--
    A ROW OF KPI CARDS (EMS, Part B §6.6): two to a row from the smallest
    phone, four from lg, above the screen's main surface. One to a row put
    four tall cards between a phone user and the list they came for.
--}}
<div {{ $attributes->class('grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4') }}>
    {{ $slot }}
</div>
