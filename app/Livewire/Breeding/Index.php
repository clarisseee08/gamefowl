<?php

declare(strict_types=1);

namespace App\Livewire\Breeding;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
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
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $bloodline = '';

    public function mount(): void
    {
        $this->authorize('viewAny', BreedingRecord::class);
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'from', 'to', 'bloodline']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->from !== '' || $this->to !== '' || $this->bloodline !== '';
    }

    /** @return LengthAwarePaginator<int, BreedingRecord> */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return BreedingRecord::query()
            // Every row renders both parents' names, so they must be loaded up
            // front - otherwise this is 2 extra queries per row against a
            // database in another country.
            ->with(['sire:id,name,band_number,bloodline', 'dam:id,name,band_number,bloodline'])
            ->between($this->from ?: null, $this->to ?: null)
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->whereHas('sire', fn ($q) => $q->whereLike('name', $term, caseSensitive: false)
                    ->orWhereLike('band_number', $term, caseSensitive: false))
                    ->orWhereHas('dam', fn ($q) => $q->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('band_number', $term, caseSensitive: false));
            })
            ->when($this->bloodline !== '', function ($query): void {
                $query->whereHas('sire', fn ($q) => $q->where('bloodline', $this->bloodline));
            })
            ->orderByDesc('mating_date')
            ->paginate(config('gfms.per_page'));
    }

    /**
     * Farm-wide averages for the filtered set.
     *
     * Computed in SQL rather than by summing model accessors, so the figures
     * cover every matching record and not just the current page.
     *
     * @return array{matings: int, eggs_set: int, eggs_fertile: int, eggs_hatched: int, fertility: float|null, hatch: float|null}
     */
    #[Computed]
    public function summary(): array
    {
        $totals = BreedingRecord::query()
            ->between($this->from ?: null, $this->to ?: null)
            ->selectRaw('count(*) as matings')
            ->selectRaw('coalesce(sum(eggs_set), 0) as eggs_set')
            ->selectRaw('coalesce(sum(eggs_fertile), 0) as eggs_fertile')
            ->selectRaw('coalesce(sum(eggs_hatched), 0) as eggs_hatched')
            ->first();

        $set = (int) ($totals->eggs_set ?? 0);
        $fertile = (int) ($totals->eggs_fertile ?? 0);
        $hatched = (int) ($totals->eggs_hatched ?? 0);

        return [
            'matings' => (int) ($totals->matings ?? 0),
            'eggs_set' => $set,
            'eggs_fertile' => $fertile,
            'eggs_hatched' => $hatched,
            'fertility' => $set > 0 ? round(($fertile / $set) * 100, 1) : null,
            'hatch' => $fertile > 0 ? round(($hatched / $fertile) * 100, 1) : null,
        ];
    }

    /** @return array<int, string> */
    #[Computed]
    public function bloodlineOptions(): array
    {
        return Broodcock::query()
            ->whereNotNull('bloodline')
            ->distinct()
            ->orderBy('bloodline')
            ->pluck('bloodline')
            ->all();
    }

    public function render(): View
    {
        return view('livewire.breeding.index')
            ->title('Breeding Records');
    }
}
