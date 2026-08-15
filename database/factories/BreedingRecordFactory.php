<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreedingRecord>
 */
class BreedingRecordFactory extends Factory
{
    protected $model = BreedingRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        // Generate a realistic, internally consistent egg funnel:
        // hatched <= fertile <= set, which the DB CHECK constraints require.
        $set = fake()->numberBetween(6, 20);
        $fertile = fake()->numberBetween((int) floor($set * 0.5), $set);
        $hatched = fake()->numberBetween((int) floor($fertile * 0.5), $fertile);

        return [
            'sire_id' => Broodcock::factory()->male(),
            'dam_id' => Broodcock::factory()->female(),
            'mating_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'eggs_set' => $set,
            'eggs_fertile' => $fertile,
            'eggs_hatched' => $hatched,
            'offspring_count' => 0,
            'notes' => fake()->optional(0.3)->sentence(),
            'recorded_by' => null,
        ];
    }

    /** A mating where every hatched chick is still unregistered. */
    public function withUnregisteredOffspring(int $hatched = 4): static
    {
        return $this->state(fn () => [
            'eggs_set' => $hatched + 4,
            'eggs_fertile' => $hatched + 2,
            'eggs_hatched' => $hatched,
            'offspring_count' => 0,
        ]);
    }

    public function fullyRegistered(): static
    {
        return $this->state(fn (array $attributes) => [
            'offspring_count' => $attributes['eggs_hatched'],
        ]);
    }

    public function forPair(Broodcock $sire, Broodcock $dam): static
    {
        return $this->state(fn () => [
            'sire_id' => $sire->id,
            'dam_id' => $dam->id,
        ]);
    }
}
