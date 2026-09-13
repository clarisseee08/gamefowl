<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\ChoosesShellByViewer;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The customer-facing catalogue, as a page of its own.
 *
 * Deliberately a separate screen from the staff broodcock index rather than a
 * role-switched version of it. A customer browsing available stock and a
 * record keeper doing data entry want genuinely different things: the
 * catalogue is photo-led, hides farm-internal vocabulary (pens, statuses like
 * "resting"), and only ever shows birds that are actually on the farm.
 *
 * WHAT THIS CLASS IS NOW. The grid, the filters and the pagination moved into
 * Catalog\Browse when the landing page began showing the catalogue too. Two
 * surfaces rendering the same stock from two implementations is how they drift
 * apart, so there is one implementation and this is the page around it.
 *
 * What remains here is what is genuinely about the PAGE rather than the
 * browsing: which shell to render in, and the title.
 *
 * Authorization is still the Policy - this class narrows what is SHOWN, it is
 * not what enforces access.
 */
/*
 * PUBLIC. No account is needed to reach this screen - it is the farm's
 * advertisement, and requiring a login to look at stock defeats the purpose.
 * BroodcockPolicy::viewAny() accepts a null user.
 *
 * THE SHELL DEPENDS ON WHO IS LOOKING - see ChoosesShellByViewer. Staff get
 * the console shell so they keep their sidebar; a customer gets the public
 * one.
 */
final class Index extends Component
{
    use ChoosesShellByViewer;

    public function mount(): void
    {
        $this->authorize('viewAny', Broodcock::class);
    }

    public function render(): View
    {
        return view('livewire.catalog.index')
            ->layout($this->viewerShell())
            ->title('Catalogue');
    }
}
