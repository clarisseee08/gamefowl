<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pen>
 */
class PenFactory extends Factory
{
    protected $model = Pen::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            // `code` is UNIQUE, so it must be unique per generated row rather
            // than a random string that can collide inside one test.
            'code' => 'P-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->randomElement(['Breeding Pen', 'Grow-out Pen', 'Conditioning Pen', 'Rest Pen'])
                .' '.fake()->randomElement(['A', 'B', 'C', 'D']),
            'location' => fake()->randomElement(['North Yard', 'South Yard', 'East Shed', 'West Shed']),
            'capacity' => fake()->numberBetween(4, 20),
            'notes' => null,
        ];
    }

    /** A pen with no stated limit - remainingCapacity() returns null. */
    public function noLimit(): static
    {
        return $this->state(fn (): array => ['capacity' => 0]);
    }

    public function capacity(int $capacity): static
    {
        return $this->state(fn (): array => ['capacity' => $capacity]);
    }
}
