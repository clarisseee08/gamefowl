<?php

declare(strict_types=1);

namespace App\Livewire\Pens;

use App\Actions\Pens\AssignBroodcockToPen;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The pen list.
 *
 * Occupancy is a COUNT of related broodcocks. Resolving it per row would fire
 * one query per pen - fifteen extra network round trips to Supabase on a
 * single page - so it is loaded with withCount() and read back through the
 * model's `broodcocks_count` attribute. PenTest::test_the_pen_index_does_not_n_plus_one
 * locks that in.
 */
#[Title('Pens')]
final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /** The pen the user is being asked to confirm deleting, if any. */
    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Pen::class);
    }

    public function updating(string $property): void
    {
        // Any filter change must return to page 1, or a search can land the
        // user on an empty page 4 of a 1-page result.
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    /** @return LengthAwarePaginator<int, Pen> */
    #[Computed]
    public function pens(): LengthAwarePaginator
    {
        return Pen::query()
            ->withCount('broodcocks')
            ->search($this->search)
            ->orderBy('code')
            ->paginate(15);
    }

    /** The pen named in the delete confirmation dialog. */
    #[Computed]
    public function penPendingDeletion(): ?Pen
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return Pen::query()
            ->withCount('broodcocks')
            ->find($this->confirmingDeleteId);
    }

    public function confirmDelete(int $penId): void
    {
        $pen = Pen::query()->findOrFail($penId);

        $this->authorize('delete', $pen);

        $this->confirmingDeleteId = $penId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        unset($this->penPendingDeletion);
    }

    /**
     * Soft-delete the pen and unassign every bird it held.
     *
     * The broodcocks FK is nullOnDelete(), but that only fires on a real
     * DELETE - a soft delete leaves pen_id pointing at a hidden row. The
     * birds are therefore unassigned explicitly, inside one transaction with
     * the delete, so the promise made in the confirmation dialog is actually
     * kept. The birds themselves are never deleted.
     */
    public function delete(AssignBroodcockToPen $unassign): void
    {
        $pen = Pen::query()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $pen);

        $name = "{$pen->code} - {$pen->name}";

        $released = DB::transaction(function () use ($pen, $unassign): int {
            $ids = $pen->broodcocks()->pluck('id')->all();

            $moved = $unassign->handle($ids, null);

            $pen->delete();

            return $moved;
        });

        $this->confirmingDeleteId = null;
        unset($this->penPendingDeletion, $this->pens);

        session()->flash('success', $released === 0
            ? "Pen {$name} was deleted."
            : "Pen {$name} was deleted. {$released} ".($released === 1 ? 'bird is' : 'birds are').' now unassigned.');
    }

    public function render(): View
    {
        return view('livewire.pens.index');
    }
}
