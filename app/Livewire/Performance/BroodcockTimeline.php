<?php

declare(strict_types=1);

namespace App\Livewire\Performance;

use App\Enums\PerformanceEventType;
use App\Livewire\Concerns\ChoosesPageSize;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Support\PerformanceSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * One bird's performance history, newest event first, with the derived
 * summary above it.
 *
 * Designed to be embedded in the broodcock detail page:
 *   <livewire:performance.broodcock-timeline :broodcock="$broodcock" />
 */
final class BroodcockTimeline extends Component
{
    use ChoosesPageSize;
    use WithPagination;

    public Broodcock $broodcock;

    #[Url(as: 'type', except: '')]
    public string $eventType = '';

    /** Record queued for deletion, held only long enough to show the dialog. */
    public ?int $confirmingDeleteId = null;

    /**
     * Confirmation shown after a delete. Held on the component rather than
     * flashed: this component is embedded in a bigger page, and Livewire
     * re-renders only this subtree, so a session flash would go unseen.
     */
    public string $statusMessage = '';

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
        $this->authorize('viewAny', PerformanceRecord::class);

        $this->broodcock = $broodcock;
    }

    public function updating(string $property): void
    {
        if ($property === 'showForm') {
            return;
        }

        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function openForm(?int $recordId = null): void
    {
        $record = $recordId !== null
            ? PerformanceRecord::query()->where('broodcock_id', $this->broodcock->id)->findOrFail($recordId)
            : null;

        $this->authorize($record ? 'update' : 'create', $record ?? PerformanceRecord::class);

        $this->formRecordId = $record?->id;
        $this->showForm = true;
    }

    /**
     * The summary is a separate aggregate query over ALL of this bird's
     * events, not a tally of the visible page. Paginating a tally would
     * quietly report "3 contests" for a bird with thirty.
     */
    #[Computed]
    public function summary(): PerformanceSummary
    {
        return PerformanceSummary::forBroodcock($this->broodcock);
    }

    /** @return LengthAwarePaginator<int, PerformanceRecord> */
    #[Computed]
    public function events(): LengthAwarePaginator
    {
        return $this->broodcock->performanceRecords()
            // The recorder's name is printed on every entry, so it is
            // eager-loaded. Model::shouldBeStrict() would throw otherwise -
            // which is the point.
            ->with('recordedBy:id,full_name')
            ->ofType($this->eventType)
            // Newest first. The composite index (broodcock_id, event_date)
            // covers exactly this ordering.
            ->orderBy('event_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->pageSize());
    }

    /** Total events regardless of the current filter - drives the empty state. */
    #[Computed]
    public function totalEvents(): int
    {
        return $this->summary->totalEvents;
    }

    /** @return array<int, PerformanceEventType> */
    public function eventTypeOptions(): array
    {
        return PerformanceEventType::cases();
    }

    // -----------------------------------------------------------------
    // Delete - owner only, soft delete, always confirmed by name
    // -----------------------------------------------------------------

    public function confirmDelete(int $recordId): void
    {
        $record = $this->findOwnedRecord($recordId);

        $this->authorize('delete', $record);

        $this->confirmingDeleteId = $recordId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $record = $this->findOwnedRecord($this->confirmingDeleteId);

        $this->authorize('delete', $record);

        $date = $record->event_date->format('d M Y');
        $type = $record->event_type->label();

        $record->delete();

        $this->confirmingDeleteId = null;

        // The computed summary and page are cached per request; clearing them
        // means the stats above the timeline reflect the deletion immediately.
        unset($this->summary, $this->events, $this->totalEvents);

        $this->statusMessage = "The {$type} record for {$this->broodcock->displayName()} on {$date} has been removed.";
    }

    public function dismissStatus(): void
    {
        $this->statusMessage = '';
    }

    #[Computed]
    public function recordPendingDeletion(): ?PerformanceRecord
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return $this->broodcock->performanceRecords()->find($this->confirmingDeleteId);
    }

    /**
     * Scoping the lookup to this bird means a crafted request cannot delete
     * another bird's record through this component.
     */
    private function findOwnedRecord(int $recordId): PerformanceRecord
    {
        /** @var PerformanceRecord $record */
        $record = $this->broodcock->performanceRecords()->findOrFail($recordId);

        return $record;
    }

    public function render(): View
    {
        return view('livewire.performance.broodcock-timeline');
    }
}
