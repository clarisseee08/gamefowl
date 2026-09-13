<?php

declare(strict_types=1);

namespace App\Actions\Broodcocks;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;

/**
 * Finds, or creates, the broodcock row standing in for a bird the farm does
 * not own.
 *
 * WHY A ROW AND NOT A NAME. `sire_id` and `dam_id` are foreign keys. A parent
 * that is not already a row cannot be recorded at all, and the obvious
 * workaround - a pair of free-text name columns - puts that parent outside the
 * pedigree: the id stays NULL and the three-generation tree loses the whole
 * branch above them. Creating a real row keeps the keys intact and keeps the
 * tree whole, which is the feature this system is built around.
 *
 * `is_external` is then what tells the two apart. An outside bird is a node in
 * a family tree, not livestock in this farm's care: she has no pen, no health
 * schedule and no mortality to record, and she is excluded from inventory
 * counts, the dashboard and the public catalogue.
 *
 * MATCHED ON NAME AND SEX, so entering the same borrowed hen on a second record
 * reuses her row instead of creating a duplicate - otherwise she becomes
 * several separate nodes in the pedigree instead of one, and the tree quietly
 * stops meaning anything.
 *
 * Extracted from Breeding\Form when the broodcock form gained the same ability.
 * Two copies of this would drift on the matching rule, and the failure would be
 * silent: a second "Ilocos Hen" nobody notices until the family tree forks.
 */
final class ResolveExternalParent
{
    public function handle(Sex $sex, string $name, ?string $bloodline = null): Broodcock
    {
        $bloodline = $bloodline !== null && trim($bloodline) !== '' ? trim($bloodline) : null;

        return Broodcock::query()->firstOrCreate(
            [
                // Trimmed, so "Ilocos Hen" and "  Ilocos Hen  " are one bird.
                'name' => trim($name),
                'sex' => $sex->value,
                'is_external' => true,
            ],
            [
                'bloodline' => $bloodline,
                'class' => BroodcockClass::Ordinary->value,
                'status' => BroodcockStatus::Active->value,
            ],
        );
    }
}
