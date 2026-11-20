<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Broodcock;

/**
 * Wright's coefficient of inbreeding (COI), worked out from the recorded
 * family tree.
 *
 * The COI of a chick is the kinship (coancestry) of its two parents: the
 * chance that the two copies of a gene it inherits are copies of one gene in
 * a shared ancestor. Half-sibling parents give 12.5%, full siblings or a
 * parent with its own offspring 25%.
 *
 * HOW. The recursive (tabular) method rather than counting paths by hand:
 *
 *   kinship(a, a) = (1 + COI(a)) / 2, where COI(a) = kinship(sire_a, dam_a)
 *   kinship(a, b) = (kinship(sire_a, b) + kinship(dam_a, b)) / 2
 *
 * where a is the YOUNGER of the two - never an ancestor of b - which is what
 * the generation rank below guarantees. An unknown parent counts as unrelated
 * to everything, so the figure is a floor: it is only as complete as the
 * parentage on file, and says so on screen.
 *
 * COST. The tree is loaded breadth-first, one query per generation (the way
 * Pedigree does it), up to GENERATIONS above the pair, and archived birds are
 * included - an archived grandsire is still a grandsire. A pair's result is
 * cached until the next write, like every other read of the records.
 */
final class Inbreeding
{
    /** How far back to look. Six generations covers anything a farm records. */
    public const GENERATIONS = 6;

    /** @var array<int, array{0: int|null, 1: int|null}> id => [sire_id, dam_id] */
    private array $parents = [];

    /** @var array<int, int> */
    private array $rank = [];

    /** @var array<string, float> */
    private array $kinship = [];

    /**
     * The COI the chicks of this pair would have, or null when either parent
     * is not chosen.
     */
    public static function ofOffspring(?int $sireId, ?int $damId): ?float
    {
        if ($sireId === null || $damId === null) {
            return null;
        }

        [$low, $high] = $sireId <= $damId ? [$sireId, $damId] : [$damId, $sireId];

        return RecordCache::remember('inbreeding:'.$low.':'.$high, function () use ($sireId, $damId): float {
            $calculation = new self;
            $calculation->load([$sireId, $damId]);

            return $calculation->kinshipOf($sireId, $damId);
        });
    }

    /**
     * A bird's own COI, or null when a parent is not on record (nothing can
     * be said, which is not the same as 0%).
     */
    public static function ofBird(Broodcock $bird): ?float
    {
        return self::ofOffspring($bird->sire_id, $bird->dam_id);
    }

    /** 0.125 → "12.5%", 0.25 → "25%", 0.03125 → "3.13%". */
    public static function percent(float $coefficient): string
    {
        return rtrim(rtrim(number_format($coefficient * 100, 2, '.', ''), '0'), '.').'%';
    }

    /**
     * The tree above $ids, without the per-bird relation queries that strict
     * mode would (rightly) refuse.
     *
     * @param  list<int>  $ids
     */
    private function load(array $ids): void
    {
        $frontier = array_values(array_unique($ids));

        for ($generation = 0; $generation <= self::GENERATIONS && $frontier !== []; $generation++) {
            $birds = Broodcock::withTrashed()
                ->whereIn('id', $frontier)
                ->get(['id', 'sire_id', 'dam_id']);

            $next = [];

            foreach ($birds as $bird) {
                $this->parents[$bird->id] = [$bird->sire_id, $bird->dam_id];

                foreach ([$bird->sire_id, $bird->dam_id] as $parentId) {
                    if ($parentId !== null && ! isset($this->parents[$parentId])) {
                        $next[] = $parentId;
                    }
                }
            }

            $frontier = array_values(array_unique($next));
        }
    }

    /** @return array{0: int|null, 1: int|null} */
    private function parentsOf(int $id): array
    {
        // A bird past the last loaded generation is treated as a founder.
        return $this->parents[$id] ?? [null, null];
    }

    /**
     * Generations of recorded ancestry below which the bird sits: a founder is
     * 0, its chick 1. An ancestor always ranks lower than its descendants.
     *
     * @param  array<int, true>  $visiting  guards a loop already in the data
     */
    private function rankOf(?int $id, array $visiting = []): int
    {
        if ($id === null || isset($visiting[$id])) {
            return 0;
        }

        if (isset($this->rank[$id])) {
            return $this->rank[$id];
        }

        $visiting[$id] = true;
        [$sire, $dam] = $this->parentsOf($id);

        $hasParents = $sire !== null || $dam !== null;

        return $this->rank[$id] = $hasParents
            ? 1 + max($this->rankOf($sire, $visiting), $this->rankOf($dam, $visiting))
            : 0;
    }

    private function kinshipOf(?int $a, ?int $b, int $depth = 0): float
    {
        // The depth cap only matters if a loop exists in the data.
        if ($a === null || $b === null || $depth > 4 * (self::GENERATIONS + 2)) {
            return 0.0;
        }

        if ($a === $b) {
            [$sire, $dam] = $this->parentsOf($a);

            return 0.5 * (1 + $this->kinshipOf($sire, $dam, $depth + 1));
        }

        $key = min($a, $b).':'.max($a, $b);

        if (isset($this->kinship[$key])) {
            return $this->kinship[$key];
        }

        // Expand the younger bird, which cannot be an ancestor of the other.
        if ($this->rankOf($a) < $this->rankOf($b)) {
            [$a, $b] = [$b, $a];
        }

        [$sire, $dam] = $this->parentsOf($a);

        return $this->kinship[$key] = 0.5 * ($this->kinshipOf($sire, $b, $depth + 1) + $this->kinshipOf($dam, $b, $depth + 1));
    }
}
