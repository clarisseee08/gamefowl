<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MortalityRecord>
 */
class MortalityRecordFactory extends Factory
{
    protected $model = MortalityRecord::class;

    /**
     * Causes a game farm actually records, not lorem ipsum - the mortality
     * report groups by this string, so realistic values make the seeded
     * breakdown look like the real thing.
     *
     * @var list<string>
     */
    public const COMMON_CAUSES = [
        'Disease',
        'Injury',
        'Predator attack',
        'Old age',
        'Heat stress',
        'Unknown',
    ];

    /** @var list<string> */
    public const DISPOSAL_METHODS = [
        'Buried',
        'Burned',
        'Composted',
        'Sent for laboratory examination',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            // Overridden in most tests; a bird can only die once, so leaving
            // this to build its own bird is the only safe default.
            'broodcock_id' => Broodcock::factory(),
            'date_of_death' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'cause_of_death' => fake()->randomElement(self::COMMON_CAUSES),
            'disposal_method' => fake()->randomElement(self::DISPOSAL_METHODS),
            'remarks' => fake()->boolean(40) ? fake()->sentence() : null,
            'recorded_by' => User::factory()->staff(),
        ];
    }

    /** Attach the record to a bird that already exists. */
    public function forBroodcock(Broodcock $broodcock): static
    {
        return $this->state(fn () => ['broodcock_id' => $broodcock->id]);
    }

    public function recordedBy(User $user): static
    {
        return $this->state(fn () => ['recorded_by' => $user->id]);
    }

    public function cause(string $cause): static
    {
        return $this->state(fn () => ['cause_of_death' => $cause]);
    }

    public function diedOn(string $date): static
    {
        return $this->state(fn () => ['date_of_death' => $date]);
    }
}
