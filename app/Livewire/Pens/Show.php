<?php

declare(strict_types=1);

namespace App\Livewire\Pens;

use App\Actions\Pens\AssignBroodcockToPen;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A single pen, with the birds currently housed in it.
 *
 * The bird list is paginated rather than dumped in full - a conditioning pen
 * can hold dozens of birds, and every row is a network round trip's worth of
 * payload from Supabase.
 */
final class Show extends Component
{
    use WithPagination;

    public int $penId;

    public bool $confirmingDelete = false;

    public function mount(Pen $pen): void
    {
        $this->authorize('view', $pen);

        $this->penId = $pen->id;
    }

    /** Always loaded with its occupancy count - occupancy() must never lazy-count. */
    #[Computed]
    public function pen(): Pen
    {
        return Pen::query()
            ->withCount('broodcocks')
            ->findOrFail($this->penId);
    }

    /** @return LengthAwarePaginator<int, Broodcock> */
    #[Computed]
    public function birds(): LengthAwarePaginator
    {
        return Broodcock::query()
            ->pen($this->penId)
            ->orderBy('name')
            ->paginate(10);
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->pen);

        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    /**
     * Soft-delete the pen and release its birds. See Index::delete() - the
     * nullOnDelete() FK never fires on a soft delete, so the birds are
     * unassigned explicitly inside the same transaction.
     */
    public function delete(AssignBroodcockToPen $unassign): void
    {
        $pen = $this->pen;

        $this->authorize('delete', $pen);

        $name = "{$pen->code} - {$pen->name}";

        $released = DB::transaction(function () use ($pen, $unassign): int {
            $moved = $unassign->handle($pen->broodcocks()->pluck('id')->all(), null);

            $pen->delete();

            return $moved;
        });

        session()->flash('success', $released === 0
            ? "Pen {$name} was deleted."
            : "Pen {$name} was deleted. {$released} ".($released === 1 ? 'bird is' : 'birds are').' now unassigned.');

        $this->redirectRoute('pens.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pens.show')
            ->title("Pen {$this->pen->code}");
    }
}
