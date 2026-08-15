<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Models\Broodcock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Broodcock>
 */
class BroodcockFactory extends Factory
{
    protected $model = Broodcock::class;

    /** Bloodlines the seeded farm actually breeds - kept realistic for the demo. */
    public const BLOODLINES = ['Sweater', 'Kelso', 'Hatch', 'Roundhead', 'Grey'];

    public const BREEDS = ['American Game', 'Asil', 'Shamo', 'Peruvian'];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $hatched = fake()->dateTimeBetween('-4 years', '-8 months');

        return [
            'band_number' => strtoupper(fake()->bothify('??-####')),
            'name' => fake()->firstName(),
            'breed' => fake()->randomElement(self::BREEDS),
            'bloodline' => fake()->randomElement(self::BLOODLINES),
            'class' => fake()->randomElement(BroodcockClass::cases()),
            'sex' => fake()->randomElement(Sex::cases()),
            'date_hatched' => $hatched,
            'date_acquired' => fake()->dateTimeBetween($hatched, 'now'),
            'weight' => fake()->randomFloat(2, 1.8, 3.2),
            'color' => fake()->randomElement(['Red', 'Black', 'White', 'Grey', 'Brown', 'Spangled']),
            'comb_type' => fake()->randomElement(['Straight', 'Pea', 'Rose']),
            'leg_color' => fake()->randomElement(['Yellow', 'White', 'Green', 'Black']),
            'distinguishing_marks' => fake()->optional(0.4)->sentence(4),
            'status' => BroodcockStatus::Active,
            'sire_id' => null,
            'dam_id' => null,
            'pen_id' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function male(): static
    {
        return $this->state(fn () => ['sex' => Sex::Male]);
    }

    public function female(): static
    {
        return $this->state(fn () => ['sex' => Sex::Female]);
    }

    public function status(BroodcockStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function deceased(): static
    {
        return $this->state(fn () => ['status' => BroodcockStatus::Deceased]);
    }

    public function bloodline(string $bloodline): static
    {
        return $this->state(fn () => ['bloodline' => $bloodline]);
    }

    /** A bird that has not been banded yet - band_number is legitimately null. */
    public function unbanded(): static
    {
        return $this->state(fn () => ['band_number' => null]);
    }

    /** Explicit parentage, for building pedigree fixtures. */
    public function bredFrom(Broodcock $sire, Broodcock $dam): static
    {
        return $this->state(fn () => [
            'sire_id' => $sire->id,
            'dam_id' => $dam->id,
            'bloodline' => $sire->bloodline,
        ]);
    }
}
