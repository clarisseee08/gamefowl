<?php

declare(strict_types=1);

namespace App\Livewire\Archive;

use App\Actions\Archive\RestoreFromArchive;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\PerformanceRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

/**
 * The Archive: every archived record, one tab per kind, kept for good.
 *
 * Nothing in the system is ever erased - "Archive" is a soft delete - so this
 * screen is where those records live and where the owner restores one that
 * was archived by mistake. Owner only, like archiving itself.
 */
final class Index extends Component
{
    use WithPagination;

    /** Tab key => [label, model class]. The order is the order of the tabs. */
    public const TABS = [
        'gamefowl' => ['Gamefowl', Broodcock::class],
        'photos' => ['Photos', BroodcockPhoto::class],
        'breeding' => ['Breeding', BreedingRecord::class],
        'health' => ['Health', HealthRecord::class],
        'performance' => ['Performance', PerformanceRecord::class],
        'mortality' => ['Mortality', MortalityRecord::class],
    ];

    #[Url(except: 'gamefowl')]
    public string $tab = 'gamefowl';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Locked]
    public ?string $restoringType = null;

    #[Locked]
    public ?int $restoringId = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        Gate::authorize('view-archive');

        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'gamefowl';
        }
    }

    public function updatingTab(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function updatedTab(string $value): void
    {
        if (! array_key_exists($value, self::TABS)) {
            $this->tab = 'gamefowl';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        return collect(self::TABS)
            ->map(fn (array $tab): int => $tab[1]::onlyTrashed()->count())
            ->all();
    }

    /** @return LengthAwarePaginator<int, Model> */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->query($this->tab)
            ->latest('deleted_at')
            ->paginate(25);
    }

    /**
     * Who archived each row on this page, from the activity log's "deleted"
     * entries. One query for the whole page, not one per row.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function archivedBy(): Collection
    {
        $ids = $this->rows->getCollection()->modelKeys();

        if ($ids === []) {
            return collect();
        }

        return Activity::query()
            ->with('causer')
            ->where('event', 'deleted')
            ->where('subject_type', self::TABS[$this->tab][1])
            ->whereIn('subject_id', $ids)
            ->latest('id')
            ->get()
            ->unique('subject_id')
            ->mapWithKeys(fn (Activity $entry): array => [
                (int) $entry->subject_id => $entry->causer?->full_name ?? 'Unknown',
            ]);
    }

    public function confirmRestore(string $type, int $id): void
    {
        Gate::authorize('view-archive');

        $record = $this->find($type, $id);
        $this->authorize('restore', $record);

        $this->restoringType = $type;
        $this->restoringId = $record->getKey();
        $this->errorMessage = null;
    }

    public function cancelRestore(): void
    {
        $this->restoringType = null;
        $this->restoringId = null;
    }

    public function restore(RestoreFromArchive $restore): void
    {
        Gate::authorize('view-archive');

        if ($this->restoringType === null || $this->restoringId === null) {
            return;
        }

        $record = $this->find($this->restoringType, $this->restoringId);
        $this->authorize('restore', $record);

        $name = $this->describe($record);

        try {
            $restore->handle($record);
        } catch (RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
            $this->cancelRestore();

            return;
        }

        $this->cancelRestore();
        $this->dispatch('toast', message: "{$name} has been restored.", tone: 'ok');

        unset($this->rows, $this->counts, $this->archivedBy);
    }

    /** The record being confirmed, for the dialog. */
    #[Computed]
    public function restoring(): ?Model
    {
        return $this->restoringType && $this->restoringId
            ? $this->find($this->restoringType, $this->restoringId)
            : null;
    }

    /** A short human name for a record: what the dialog and the message call it. */
    public function describe(Model $record): string
    {
        return match (true) {
            $record instanceof Broodcock => $record->name,
            $record instanceof BroodcockPhoto => 'The photo of '.($record->broodcock?->displayName() ?? 'a removed bird'),
            $record instanceof BreedingRecord => 'The mating of '.($record->sire?->name ?? 'a removed bird').' × '.($record->dam?->name ?? 'a removed bird'),
            $record instanceof HealthRecord => 'The '.mb_strtolower($record->record_type->label()).' record for '.($record->broodcock?->name ?? 'a removed bird'),
            $record instanceof PerformanceRecord => 'The '.mb_strtolower($record->event_type->label()).' record for '.($record->broodcock?->name ?? 'a removed bird'),
            $record instanceof MortalityRecord => 'The death record for '.($record->broodcock?->name ?? 'a removed bird'),
            default => 'The record',
        };
    }

    private function find(string $type, int $id): Model
    {
        abort_unless(array_key_exists($type, self::TABS), 404);

        return $this->query($type)->findOrFail($id);
    }

    /**
     * Archived rows of one kind, with what each needs to be shown and found.
     * Related birds load with withTrashed(): a record whose bird was archived
     * too must still name it.
     *
     * The search ignores case, as every other list's does: whereLike(caseSensitive:
     * false) compiles to ILIKE on Postgres. A plain 'like' is case-sensitive
     * there, so "reyna" found nothing in production while SQLite, which ignores
     * case anyway, let every test pass.
     *
     * @return Builder<Model>
     */
    private function query(string $type): Builder
    {
        $bird = fn ($query) => $query->withTrashed();
        $term = trim($this->search);
        $like = '%'.$term.'%';
        $birdMatches = fn (Builder $query) => $query->withTrashed()
            ->where(fn (Builder $q) => $q->whereLike('name', $like, caseSensitive: false)
                ->orWhereLike('band_number', $like, caseSensitive: false));

        return match ($type) {
            'gamefowl' => Broodcock::onlyTrashed()
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('name', $like, caseSensitive: false)
                    ->orWhereLike('band_number', $like, caseSensitive: false)
                    ->orWhereLike('bloodline', $like, caseSensitive: false))),
            'photos' => BroodcockPhoto::onlyTrashed()
                ->with(['broodcock' => $bird])
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('caption', $like, caseSensitive: false)
                    ->orWhereHas('broodcock', $birdMatches))),
            'breeding' => BreedingRecord::onlyTrashed()
                ->with(['sire' => $bird, 'dam' => $bird])
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereHas('sire', $birdMatches)
                    ->orWhereHas('dam', $birdMatches))),
            'health' => HealthRecord::onlyTrashed()
                ->with(['broodcock' => $bird])
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('product_name', $like, caseSensitive: false)
                    ->orWhereHas('broodcock', $birdMatches))),
            'performance' => PerformanceRecord::onlyTrashed()
                ->with(['broodcock' => $bird])
                ->when($term !== '', fn (Builder $q) => $q->whereHas('broodcock', $birdMatches)),
            'mortality' => MortalityRecord::onlyTrashed()
                ->with(['broodcock' => $bird])
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('cause_of_death', $like, caseSensitive: false)
                    ->orWhereHas('broodcock', $birdMatches))),
        };
    }

    public function render(): View
    {
        return view('livewire.archive.index')
            ->title('Archive');
    }
}
