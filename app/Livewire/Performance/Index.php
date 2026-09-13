<?php

declare(strict_types=1);

namespace App\Livewire\Performance;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\PerformanceRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $eventType = '';

    #[Url(except: '')]
    public string $result = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: 'event_date')]
    public string $sortBy = 'event_date';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    /** Record queued for deletion, held only long enough to show the dialog. */
    public ?int $confirmingDeleteId = null;

    /**
     * Confirmation shown after a delete.
     *
     * Held on the component rather than flashed to the session: Livewire
     * re-renders only this component, so a session flash would sit in the
     * layout unnoticed until the next full page load.
     */
    public string $statusMessage = '';

    /** Columns a user may sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['event_date', 'event_type', 'result', 'weight', 'rating', 'created_at'];

    public function mount(): void
    {
        $this->authorize('viewAny', PerformanceRecord::class);
    }

    /** Any filter change must return to page 1, or the user lands on an empty page. */
    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'eventType', 'result', 'from', 'to']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->eventType !== ''
            || $this->result !== ''
            || $this->from !== ''
            || $this->to !== '';
    }

    // -----------------------------------------------------------------
    // Delete - owner only, soft delete, always confirmed by name
    // -----------------------------------------------------------------

    public function confirmDelete(int $recordId): void
    {
        $record = PerformanceRecord::query()->findOrFail($recordId);

        // Checked when the dialog OPENS as well as when it is submitted, so a
        // user is never shown a confirmation for something they cannot do.
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

        $record = PerformanceRecord::query()->with('broodcock:id,name,band_number,bloodline')->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $record);

        $name = $record->broodcock?->displayName() ?? 'this bird';
        $date = $record->event_date->format('d M Y');

        // Soft delete. Nothing is ever hard-deleted in a system whose selling
        // point is the audit trail.
        $record->delete();

        $this->confirmingDeleteId = null;

        $this->statusMessage = "The {$record->event_type->label()} record for {$name} on {$date} has been removed.";

        $this->resetPage();
    }

    public function dismissStatus(): void
    {
        $this->statusMessage = '';
    }

    /** The record awaiting confirmation, so the dialog can name it. */
    #[Computed]
    public function recordPendingDeletion(): ?PerformanceRecord
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return PerformanceRecord::query()
            ->with('broodcock:id,name,band_number,bloodline')
            ->find($this->confirmingDeleteId);
    }

    // -----------------------------------------------------------------
    // Data
    // -----------------------------------------------------------------

    /** @return LengthAwarePaginator<int, PerformanceRecord> */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'event_date';
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return PerformanceRecord::query()
            // Every row prints the bird and the recorder. Without these two
            // eager loads the page fires 2 extra queries per row, and each one
            // is a network round trip to Supabase.
            ->with(['broodcock:id,name,band_number,bloodline', 'recordedBy:id,full_name'])
            ->when($this->search !== '', fn ($query) => $query->whereHas(
                'broodcock',
                fn ($birds) => $birds->search($this->search),
            ))
            ->ofType($this->eventType)
            ->withResult($this->result)
            ->between($this->from ?: null, $this->to ?: null)
            ->orderBy($sortBy, $direction)
            // A stable tiebreaker: without it, two events on the same date can
            // swap places between pages and a row is silently skipped.
            ->orderBy('id', 'desc')
            ->paginate(config('gfms.per_page'));
    }

    /** @return array<int, PerformanceEventType> */
    public function eventTypeOptions(): array
    {
        return PerformanceEventType::cases();
    }

    /** @return array<int, PerformanceResult> */
    public function resultOptions(): array
    {
        return PerformanceResult::cases();
    }

    public function render(): View
    {
        return view('livewire.performance.index')
            ->title('Performance Records');
    }
}
