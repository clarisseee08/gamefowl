<?php

declare(strict_types=1);

namespace App\Livewire\Mortality;

use App\Actions\Mortality\DeleteMortality;
use App\Models\MortalityRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The mortality register.
 *
 * Mortality is internal farm data - MortalityRecordPolicy::viewAny() keeps
 * customers out, and it is checked in mount() before anything is queried.
 */
#[Title('Mortality')]
final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'from')]
    public string $from = '';

    #[Url(as: 'to')]
    public string $to = '';

    #[Url(as: 'cause')]
    public string $cause = '';

    /** Id of the record the user is being asked to confirm deleting. */
    public ?int $confirmingDeleteId = null;

    /** In-component banner - a Livewire update does not re-render the layout. */
    public ?string $status = null;

    public function mount(): void
    {
        $this->authorize('viewAny', MortalityRecord::class);
    }

    public function updating(string $property): void
    {
        // Any filter change must return to page 1, or the user lands on an
        // empty page and thinks the filter found nothing.
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['from', 'to', 'cause']);
        $this->resetPage();
    }

    // -----------------------------------------------------------------
    // Delete - owner only, soft delete, confirmed by name
    // -----------------------------------------------------------------

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(DeleteMortality $action): void
    {
        if ($this->confirmingDeleteId === null) {
            return;
        }

        $record = MortalityRecord::with('broodcock')->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $record);

        $name = $record->broodcock?->displayName() ?? 'this bird';

        $action->handle($record);

        $this->confirmingDeleteId = null;
        $this->status = "The death record for {$name} was removed. The bird is back on the active list.";

        // Recompute every derived value - the totals and the page both changed.
        unset($this->rows, $this->summary, $this->causeBreakdown, $this->causeOptions);
        $this->resetPage();
    }

    // -----------------------------------------------------------------
    // Queries
    // -----------------------------------------------------------------

    /**
     * The filtered set, without pagination. Shared by the table and by the
     * by-cause breakdown so the two can never disagree.
     *
     * @return Builder<MortalityRecord>
     */
    private function filtered(): Builder
    {
        return MortalityRecord::query()
            ->between($this->from, $this->to)
            // cause_of_death is free text, so there is no enum to scope on.
            ->when($this->cause !== '', fn (Builder $query) => $query->where('cause_of_death', $this->cause));
    }

    /** @return LengthAwarePaginator<int, MortalityRecord> */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->filtered()
            // Eager-loaded: the table shows the bird and the recorder on every
            // row, and strict mode turns a missed relation into an exception.
            ->with(['broodcock:id,name,band_number,date_hatched', 'recordedBy:id,full_name'])
            ->orderByDesc('date_of_death')
            ->orderByDesc('id')
            ->paginate((int) config('gfms.per_page', 15));
    }

    /**
     * Deaths this month and this year, in one round trip. Neither number is
     * stored - a stored count contradicts its own rows the moment one is added.
     *
     * @return array{this_month: int, this_year: int, total: int}
     */
    #[Computed]
    public function summary(): array
    {
        $now = Carbon::now();

        $row = MortalityRecord::query()
            ->selectRaw('count(*) as total_all')
            ->selectRaw('sum(case when date_of_death between ? and ? then 1 else 0 end) as month_count', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('sum(case when date_of_death between ? and ? then 1 else 0 end) as year_count', [
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->endOfYear()->toDateString(),
            ])
            ->first();

        return [
            'this_month' => (int) ($row?->month_count ?? 0),
            'this_year' => (int) ($row?->year_count ?? 0),
            'total' => (int) ($row?->total_all ?? 0),
        ];
    }

    /**
     * Deaths grouped by cause, for whatever the filters currently show.
     *
     * @return Collection<int, array{cause: string, total: int}>
     */
    #[Computed]
    public function causeBreakdown(): Collection
    {
        return $this->filtered()
            ->selectRaw('cause_of_death, count(*) as cause_total')
            ->groupBy('cause_of_death')
            ->orderByRaw('count(*) desc')
            ->get()
            ->map(fn (MortalityRecord $record): array => [
                'cause' => (string) $record->cause_of_death,
                'total' => (int) $record->cause_total,
            ]);
    }

    /**
     * Causes that actually appear in the register, so the filter never offers
     * an option that returns nothing.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function causeOptions(): array
    {
        return MortalityRecord::query()
            ->select('cause_of_death')
            ->distinct()
            ->orderBy('cause_of_death')
            ->get()
            ->map(fn (MortalityRecord $record): string => (string) $record->cause_of_death)
            ->all();
    }

    #[Computed]
    public function confirmingRecord(): ?MortalityRecord
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return MortalityRecord::with('broodcock:id,name,band_number')
            ->find($this->confirmingDeleteId);
    }

    /** Drives the "Record a Death" button - the policy is still the real gate. */
    #[Computed]
    public function canCreate(): bool
    {
        return Auth::user()?->can('create', MortalityRecord::class) ?? false;
    }

    #[Computed]
    public function canDelete(): bool
    {
        return Auth::user()?->isOwner() ?? false;
    }

    public function hasFilters(): bool
    {
        return $this->from !== '' || $this->to !== '' || $this->cause !== '';
    }

    public function render(): View
    {
        return view('livewire.mortality.index');
    }
}
