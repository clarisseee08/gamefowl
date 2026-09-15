<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

use App\Livewire\Concerns\ChoosesShellByViewer;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Ancestor tree, as deep as config('gfms.pedigree_generations') says.
 *
 * This is the feature that turns `bloodline` from a text label into real
 * traceability, and it is the clearest differentiator from the prior systems
 * reviewed in the thesis.
 *
 * IT IS CURRENTLY SET TO ONE GENERATION - the sire and dam only. The farm
 * asked for grandparents and great-grandparents to go: they are not recorded
 * in practice, so two thirds of the chart was the words "Not recorded" and a
 * screen meant to prove traceability read as a screen that had failed.
 *
 * PERFORMANCE - read before raising that number back.
 *
 * The obvious implementation is nested eager loading:
 *     ->with(['sire.sire.sire', 'sire.sire.dam', 'dam.dam.dam', ...])
 * That looks right and is badly wrong. Laravel issues ONE QUERY PER RELATION
 * PATH, so a full three-deep binary tree costs 2 + 4 + 8 = 14 queries - no
 * better than walking the tree lazily. Measured: 45 queries for one page.
 *
 * Instead this loads the tree BREADTH-FIRST, one query per generation: collect
 * the parent ids of the current level, fetch that whole level in a single
 * `whereIn`, repeat. The cost is therefore 1 + the configured depth, whatever
 * the depth is, and it does not move as a pedigree gets more complete: two
 * queries today, four if three generations ever come back. Every query here is
 * a round trip to Supabase in Tokyo, so this matters.
 *
 * PedigreePerformanceTest asserts the query count, so a regression fails CI
 * rather than quietly making the page slow.
 */
final class Pedigree extends Component
{
    use ChoosesShellByViewer;

    public Broodcock $broodcock;

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('view', $broodcock);

        $this->broodcock = $broodcock;
    }

    /**
     * Every bird in the tree, keyed by id, fetched one generation at a time.
     *
     * @return array<int, Broodcock>
     */
    #[Computed]
    public function ancestors(): array
    {
        $root = Broodcock::query()->findOrFail($this->broodcock->id);

        /** @var array<int, Broodcock> $map */
        $map = [$root->id => $root];
        $frontier = [$root];

        for ($generation = 0; $generation < (int) config('gfms.pedigree_generations'); $generation++) {
            $parentIds = [];

            foreach ($frontier as $bird) {
                // Read the FK columns directly rather than the sire/dam
                // relations - Model::shouldBeStrict() would (correctly) throw
                // on an un-eager-loaded relation, and the whole point here is
                // to avoid per-bird relation queries.
                if ($bird->sire_id !== null) {
                    $parentIds[] = $bird->sire_id;
                }
                if ($bird->dam_id !== null) {
                    $parentIds[] = $bird->dam_id;
                }
            }

            $parentIds = array_values(array_unique($parentIds));

            if ($parentIds === []) {
                break;   // pedigree ends here; no query needed
            }

            $level = Broodcock::query()->whereIn('id', $parentIds)->get();

            foreach ($level as $bird) {
                $map[$bird->id] = $bird;
            }

            $frontier = $level->all();
        }

        return $map;
    }

    /**
     * The tree flattened into chart columns.
     *
     * A pedigree is conventionally drawn as a bracket, each column twice the
     * width of the one before it: the subject, then 2 parents, then 4
     * grandparents if the configured depth ever asks for them. Each column is
     * a fixed-length list in which a missing ancestor is null, so the template
     * renders an even grid without special-casing gaps.
     *
     * @return array<int, array<int, Broodcock|null>>
     */
    #[Computed]
    public function generations(): array
    {
        $map = $this->ancestors;   // property access - #[Computed] memoizes only on property access

        $root = $map[$this->broodcock->id] ?? null;

        $columns = [[$root]];
        $current = [$root];

        for ($generation = 0; $generation < (int) config('gfms.pedigree_generations'); $generation++) {
            $next = [];

            foreach ($current as $bird) {
                $next[] = $bird?->sire_id !== null ? ($map[$bird->sire_id] ?? null) : null;
                $next[] = $bird?->dam_id !== null ? ($map[$bird->dam_id] ?? null) : null;
            }

            $columns[] = $next;
            $current = $next;
        }

        return $columns;
    }

    /**
     * How many of the possible ancestor slots are actually recorded.
     *
     * @return array{known: int, total: int, percent: int}
     */
    #[Computed]
    public function completeness(): array
    {
        $known = 0;
        $total = 0;

        // Skip column 0 - that is the bird itself, not an ancestor.
        foreach (array_slice($this->generations, 1) as $column) {
            foreach ($column as $ancestor) {
                $total++;

                if ($ancestor !== null) {
                    $known++;
                }
            }
        }

        return [
            'known' => $known,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($known / $total) * 100) : 0,
        ];
    }

    #[Computed]
    public function root(): Broodcock
    {
        return $this->ancestors[$this->broodcock->id];
    }

    public function render(): View
    {
        return view('livewire.broodcocks.pedigree')
            ->layout($this->viewerShell())
            ->title($this->broodcock->name.' — family tree');
    }
}
