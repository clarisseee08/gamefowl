<?php

declare(strict_types=1);

namespace App\Actions\Breeding;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates broodcock records for chicks that hatched from a breeding record,
 * with sire_id and dam_id already filled in.
 *
 * This is the hinge of the whole pedigree feature. Without it, parentage has
 * to be typed by hand on every bird and in practice will not be - which is
 * exactly why the thesis's original "bloodline as a text label" design failed
 * to deliver traceability. Generating the offspring from the hatch record
 * means the family tree is a by-product of normal data entry rather than
 * extra work.
 */
final class GenerateOffspring
{
    /**
     * @param  int  $count  How many chicks to register.
     * @param  array<string, mixed>  $defaults  Shared attributes (bloodline, pen, class...).
     * @return Collection<int, Broodcock>
     */
    public function handle(BreedingRecord $record, int $count, array $defaults = []): Collection
    {
        if ($count < 1) {
            throw new RuntimeException('The number of offspring to register must be at least one.');
        }

        $remaining = $record->unregisteredOffspring();

        if ($count > $remaining) {
            throw new RuntimeException(
                "This hatch has {$remaining} unregistered chick(s); you asked to register {$count}."
            );
        }

        // Both the new birds and the parent record's offspring_count must move
        // together. A partial success here would silently corrupt the count
        // that `unregisteredOffspring()` is derived from, letting the same
        // chicks be registered twice.
        return DB::transaction(function () use ($record, $count, $defaults): Collection {
            // Lock the row so two people clicking "generate" at the same time
            // cannot both pass the remaining-count check above.
            $locked = BreedingRecord::query()
                ->whereKey($record->id)
                ->lockForUpdate()
                ->firstOrFail();

            $stillRemaining = max(0, $locked->eggs_hatched - $locked->offspring_count);

            if ($count > $stillRemaining) {
                throw new RuntimeException(
                    "This hatch now has only {$stillRemaining} unregistered chick(s) - someone may have just registered some."
                );
            }

            $sire = $locked->sire()->firstOrFail();
            $dam = $locked->dam()->firstOrFail();

            // Chicks inherit the sire's bloodline by convention on this farm;
            // the caller can override it.
            $bloodline = $defaults['bloodline'] ?? $sire->bloodline ?? $dam->bloodline;

            $created = new Collection;
            $startIndex = $locked->offspring_count;

            for ($i = 0; $i < $count; $i++) {
                $created->push(Broodcock::create([
                    // Deliberately NOT banded. Birds are banded at a certain
                    // age, not at hatch, so a generated band number would be
                    // a fiction the farm then has to reconcile.
                    'band_number' => null,
                    'name' => $this->provisionalName($locked, $startIndex + $i + 1),
                    'breed' => $defaults['breed'] ?? $sire->breed ?? $dam->breed,
                    'bloodline' => $bloodline,
                    'class' => $defaults['class'] ?? BroodcockClass::Ordinary,
                    // Sex is unknown at hatch and cannot be guessed; it is
                    // recorded later. Male is the column default only because
                    // the enum requires a value - the UI prompts for it.
                    'sex' => $defaults['sex'] ?? Sex::Male,
                    'date_hatched' => $defaults['date_hatched'] ?? $locked->mating_date->copy()->addDays(21),
                    'date_acquired' => $defaults['date_hatched'] ?? $locked->mating_date->copy()->addDays(21),
                    'status' => BroodcockStatus::Active,
                    'sire_id' => $locked->sire_id,
                    'dam_id' => $locked->dam_id,
                    'pen_id' => $defaults['pen_id'] ?? null,
                    'notes' => "Registered automatically from the hatch recorded on {$locked->mating_date->format('j M Y')}.",
                ]));
            }

            $locked->update(['offspring_count' => $locked->offspring_count + $count]);

            return $created;
        });
    }

    /**
     * A readable placeholder name so the list is navigable before staff rename
     * the birds - "Chick 3 of Bruno x Bella" beats an empty cell.
     */
    private function provisionalName(BreedingRecord $record, int $ordinal): string
    {
        return sprintf('Chick %d (%s)', $ordinal, $record->mating_date->format('M Y'));
    }
}
