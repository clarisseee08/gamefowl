<?php

declare(strict_types=1);

namespace App\Livewire\Performance;

use App\Enums\PerformanceEventType;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Support\PerformanceSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
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
    use WithPagination;

    public Broodcock $broodcock;

    #[Url(as: 'type', except: '')]
    public string $eventType = '';

    /** Record queued for deletion, held only long enough to show the dialog. */
    public ?int $confirmingDeleteId = null;

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('viewAny', PerformanceRecord::class);

        $this->broodcock = $broodcock;
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
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
            ->paginate(config('gfms.per_page'), pageName: 'timelinePage');
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

        session()->flash('success', "The {$type} record for {$this->broodcock->displayName()} on {$date} has been removed.");
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
