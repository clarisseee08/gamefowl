<?php

declare(strict_types=1);

namespace App\Livewire\Health;

use App\Enums\HealthRecordType;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The farm-wide health record list.
 *
 * Every filter is a URL property, so a staff member can bookmark or share
 * "all overdue dewormings for pen 3" and it survives a refresh.
 */
#[Title('Health Records')]
final class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $recordType = '';

    #[Url(as: 'bird', except: '')]
    public string $broodcockId = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    /** Id of the record the user is being asked to confirm deletion of. */
    public ?int $confirmingDeleteId = null;

    /**
     * Confirmation shown after a delete. Held on the component rather than
     * flashed to the session, because deleting does not reload the layout that
     * renders session flash messages - the user would never see it.
     */
    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', HealthRecord::class);
    }

    /** Any filter change must return to page 1, or the user lands on an empty page. */
    public function updating(string $property): void
    {
        if ($property !== 'page' && ! str_starts_with($property, 'paginators')) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'recordType', 'broodcockId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    // -----------------------------------------------------------------
    // Deleting - owner only, soft delete, confirmed by name first
    // -----------------------------------------------------------------

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** The record awaiting confirmation, so the dialog can name it. */
    #[Computed]
    public function recordPendingDeletion(): ?HealthRecord
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return HealthRecord::query()
            ->with('broodcock')
            ->find($this->confirmingDeleteId);
    }

    public function delete(): void
    {
        $record = $this->recordPendingDeletion;

        if ($record === null) {
            $this->confirmingDeleteId = null;

            return;
        }

        // The Policy is the gate. Hiding the button was only a courtesy.
        $this->authorize('delete', $record);

        $bird = $record->broodcock->name;
        $type = $record->record_type->label();

        $record->delete();

        $this->confirmingDeleteId = null;
        unset($this->rows, $this->recordPendingDeletion);

        $this->statusMessage = "The {$type} record for {$bird} was deleted.";
    }

    public function dismissStatus(): void
    {
        $this->statusMessage = null;
    }

    // -----------------------------------------------------------------
    // Queries
    // -----------------------------------------------------------------

    /** @return LengthAwarePaginator<int, HealthRecord> */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return HealthRecord::query()
            // Eager-loaded because every row prints the bird's name and band
            // number. Without this the list is one extra query per row, and
            // every query is a network round trip to Supabase.
            ->with('broodcock')
            ->ofType($this->recordType)
            ->between($this->normalisedDate($this->dateFrom), $this->normalisedDate($this->dateTo))
            ->when($this->broodcockId !== '', fn (Builder $query) => $query->where('broodcock_id', (int) $this->broodcockId))
            ->when($this->search !== '', fn (Builder $query) => $this->applySearch($query))
            ->orderByDesc('checkup_date')
            ->orderByDesc('id')
            ->paginate(config('gfms.per_page'));
    }

    /**
     * Birds for the filter dropdown. Only the three columns the dropdown
     * actually prints are selected.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function birds(): Collection
    {
        return Broodcock::query()
            ->select(['id', 'name', 'band_number'])
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, HealthRecordType> */
    #[Computed]
    public function recordTypes(): array
    {
        return HealthRecordType::cases();
    }

    public function render(): View
    {
        return view('livewire.health.index');
    }

    /**
     * Free-text search across the record and the bird it belongs to.
     *
     * whereLike(caseSensitive: false) compiles to ILIKE on Postgres and to
     * LIKE on SQLite, so the same code serves production and the test suite.
     *
     * @param  Builder<HealthRecord>  $query
     */
    private function applySearch(Builder $query): void
    {
        $term = trim($this->search);

        $query->where(function (Builder $outer) use ($term): void {
            $outer->whereLike('product_name', "%{$term}%", caseSensitive: false)
                ->orWhereLike('condition', "%{$term}%", caseSensitive: false)
                ->orWhereHas('broodcock', function (Builder $bird) use ($term): void {
                    $bird->whereLike('name', "%{$term}%", caseSensitive: false)
                        ->orWhereLike('band_number', "%{$term}%", caseSensitive: false);
                });
        });
    }

    /** Blank date inputs arrive as '', which the model scope must see as "no filter". */
    private function normalisedDate(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
