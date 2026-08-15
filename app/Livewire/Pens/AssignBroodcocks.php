<?php

declare(strict_types=1);

namespace App\Livewire\Pens;

use App\Actions\Pens\AssignBroodcockToPen;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Move birds into or out of one pen.
 *
 * Over-capacity is a WARNING, never a block. Farms overfill pens - if the
 * system refused the write, the records would stop matching the yard, which
 * is worse than an over-full pen showing as over-full.
 */
final class AssignBroodcocks extends Component
{
    use WithPagination;

    public int $penId;

    #[Url]
    public string $search = '';

    /**
     * Ticked birds waiting to be moved in. Livewire round-trips checkbox
     * values as strings, hence the loose type.
     *
     * @var array<int, int|string>
     */
    public array $selected = [];

    public function mount(Pen $pen): void
    {
        $this->authorize('update', $pen);

        $this->penId = $pen->id;
    }

    public function updating(string $property): void
    {
        if ($property === 'search') {
            $this->resetPage('availablePage');
        }
    }

    #[Computed]
    public function pen(): Pen
    {
        return Pen::query()
            ->withCount('broodcocks')
            ->findOrFail($this->penId);
    }

    /**
     * Birds that could be moved in: everything still on the farm that is not
     * already in this pen. `with('pen')` is what stops the "currently in"
     * column from firing a query per row.
     *
     * @return LengthAwarePaginator<int, Broodcock>
     */
    #[Computed]
    public function available(): LengthAwarePaginator
    {
        return Broodcock::query()
            ->with('pen')
            ->onFarm()
            ->where(function ($query): void {
                $query->whereNull('pen_id')->orWhere('pen_id', '!=', $this->penId);
            })
            ->search($this->search)
            ->orderBy('name')
            ->paginate(10, pageName: 'availablePage');
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function housed(): LengthAwarePaginator
    {
        return Broodcock::query()
            ->pen($this->penId)
            ->orderBy('name')
            ->paginate(10, pageName: 'housedPage');
    }

    /** Occupancy the pen would have if everything ticked were moved in now. */
    #[Computed]
    public function projectedOccupancy(): int
    {
        return $this->pen->occupancy() + count($this->selected);
    }

    /** How many birds over the stated capacity that would leave the pen. 0 = fine. */
    #[Computed]
    public function overCapacityBy(): int
    {
        if ($this->pen->capacity <= 0) {
            return 0;
        }

        return max(0, $this->projectedOccupancy - $this->pen->capacity);
    }

    public function assign(AssignBroodcockToPen $action): void
    {
        $pen = $this->pen;

        $this->authorize('update', $pen);

        if ($this->selected === []) {
            $this->addError('selected', 'Please tick at least one bird to move into this pen.');

            return;
        }

        $moved = $action->handle($this->selected, $pen);

        $this->selected = [];
        $this->refreshLists();

        session()->flash('success', $moved === 1
            ? "1 bird was moved into pen {$pen->code}."
            : "{$moved} birds were moved into pen {$pen->code}.");
    }

    public function unassign(int $broodcockId, AssignBroodcockToPen $action): void
    {
        $pen = $this->pen;

        $this->authorize('update', $pen);

        $bird = Broodcock::query()->findOrFail($broodcockId);

        $action->handle([$bird->id], null);

        $this->refreshLists();

        session()->flash('success', "{$bird->name} was removed from pen {$pen->code} and is now unassigned.");
    }

    private function refreshLists(): void
    {
        unset($this->pen, $this->available, $this->housed, $this->projectedOccupancy, $this->overCapacityBy);
    }

    public function render(): View
    {
        return view('livewire.pens.assign-broodcocks')
            ->title("Assign Birds - Pen {$this->pen->code}");
    }
}
