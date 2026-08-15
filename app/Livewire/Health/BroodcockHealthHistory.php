<?php

declare(strict_types=1);

namespace App\Livewire\Health;

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
    use WithPagination;

    /** Locked: the browser cannot repoint this component at a different bird. */
    #[Locked]
    public Broodcock $broodcock;

    /** How many rows to show. Kept small - this is a panel, not a full screen. */
    public int $perPage = 10;

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('viewAny', HealthRecord::class);

        $this->broodcock = $broodcock;
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
            ->paginate($this->perPage, pageName: 'historyPage');
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
