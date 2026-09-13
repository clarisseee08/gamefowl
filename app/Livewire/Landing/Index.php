<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The farm's front door.
 *
 * `/` used to be a redirect with a comment explaining that the front door
 * depends on who is knocking. Half of that is still true - staff go to the
 * dashboard, because their working day starts there and an advertisement is
 * no use to them - but the other half sent the public to /catalog, which
 * meant the farm's website opened on a grid of birds with nothing saying
 * whose farm it was or how to reach it.
 *
 * So this page exists, and the catalogue is ON it rather than being it.
 *
 * THE WHOLE CATALOGUE, with its filters, not a curated strip. That was a
 * deliberate choice and it carries a known cost: a page holding both a
 * filterable grid and a form is heavier than either alone, and the grid
 * resizing moves what sits below it. Both are mitigated rather than ignored -
 * the grid is paginated to twelve, which bounds the movement to a single row,
 * and the hero anchors straight to the form so nobody has to scroll past the
 * stock to reach it.
 */
final class Index extends Component
{
    public function mount(): void
    {
        /*
         * Staff never see this page.
         *
         * Not an authorization rule - there is nothing here a member of staff
         * may not look at. It is that the dashboard is where their work is,
         * and landing them on the shop window every morning would be a step to
         * click past.
         */
        if (auth()->user()?->isInternal()) {
            $this->redirectRoute('dashboard', navigate: false);
        }
    }

    public function render(): View
    {
        return view('livewire.landing.index')
            ->layout('layouts::catalog')
            ->title(config('gfms.farm.name'));
    }
}
