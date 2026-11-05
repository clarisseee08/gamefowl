<?php

declare(strict_types=1);

namespace App\Livewire\Health;

use App\Livewire\Concerns\ChoosesPageSize;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * One bird's health history, newest first.
 *
 * Designed to be embedded in the broodcock detail page:
 *
 *     <livewire:health.broodcock-health-history :broodcock="$broodcock" />
 *
 * It paginates under its own page name (`historyPage`) so it can sit on a page
 * beside other paginated lists without the two fighting over `?page=`.
 *
 * The bird is never re-read through its relation here - the records are queried
 * directly by broodcock_id, so embedding this costs one extra query, not one
 * per row.
 */
final class BroodcockHealthHistory extends Component
{
    use ChoosesPageSize;
    use WithPagination;

    /** Locked: the browser cannot repoint this component at a different bird. */
    #[Locked]
    public Broodcock $broodcock;

    /**
     * The add / edit form, in a dialog over the bird's page rather than on
     * the list, so recording something about this bird never leaves it.
     */
    public bool $showForm = false;

    #[Locked]
    public ?int $formRecordId = null;

    /**
     * A new form is built ahead, so Add opens instantly. Closing the dialog
     * drops any edit and re-keys the new form, so the next Add is blank.
     */
    public int $formNonce = 0;

    public function updatedShowForm(bool $open): void
    {
        if ($open) {
            return;
        }

        $this->formRecordId = null;
        $this->formNonce++;
    }

    /** Called by the dialog when a form that was typed in is closed: start the next one blank. */
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->updatedShowForm(false);
    }

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('viewAny', HealthRecord::class);

        $this->broodcock = $broodcock;
    }

    public function openForm(?int $recordId = null): void
    {
        $record = $recordId !== null
            ? HealthRecord::query()->where('broodcock_id', $this->broodcock->id)->findOrFail($recordId)
            : null;

        $this->authorize($record ? 'update' : 'create', $record ?? HealthRecord::class);

        $this->formRecordId = $record?->id;
        $this->showForm = true;
    }

    /**
     * This bird's records, newest check-up first.
     *
     * @return LengthAwarePaginator<int, HealthRecord>
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return HealthRecord::query()
            ->where('broodcock_id', $this->broodcock->id)
            ->orderByDesc('checkup_date')
            ->orderByDesc('id')
            ->paginate($this->pageSize(), pageName: 'historyPage');
    }

    /** The next follow-up this bird is actually waiting on, if any. */
    #[Computed]
    public function nextFollowUp(): ?HealthRecord
    {
        return HealthRecord::query()
            ->where('broodcock_id', $this->broodcock->id)
            ->whereNotNull('next_due_date')
            ->orderBy('next_due_date')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.health.broodcock-health-history');
    }
}
