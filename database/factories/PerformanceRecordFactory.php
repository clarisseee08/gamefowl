<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformanceRecord>
 */
class PerformanceRecordFactory extends Factory
{
    protected $model = PerformanceRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        // The default is a contest, because that is the case the win-rate
        // statistics actually exercise. Non-contest events are opt-in states.
        $type = fake()->randomElement([PerformanceEventType::Sparring, PerformanceEventType::Derby]);

        return [
            'broodcock_id' => Broodcock::factory(),
            'event_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'event_type' => $type,
            'weight' => fake()->randomFloat(2, 1.6, 3.2),
            'result' => fake()->randomElement([
                PerformanceResult::Win,
                PerformanceResult::Loss,
                PerformanceResult::Draw,
            ]),
            'duration_seconds' => fake()->numberBetween(30, 600),
            'rating' => fake()->numberBetween(1, 5),
            'remarks' => fake()->optional()->sentence(),
            'recorded_by' => User::factory()->staff(),
        ];
    }

    public function win(): static
    {
        return $this->state(fn (): array => ['result' => PerformanceResult::Win]);
    }

    public function loss(): static
    {
        return $this->state(fn (): array => ['result' => PerformanceResult::Loss]);
    }

    public function draw(): static
    {
        return $this->state(fn (): array => ['result' => PerformanceResult::Draw]);
    }

    /**
     * A conditioning session: no winner, so the result is "not applicable".
     * These must never land in the win-rate denominator.
     */
    public function conditioning(): static
    {
        return $this->state(fn (): array => [
            'event_type' => PerformanceEventType::Conditioning,
            'result' => PerformanceResult::NotApplicable,
            'duration_seconds' => fake()->numberBetween(600, 3600),
        ]);
    }

    public function weighIn(): static
    {
        return $this->state(fn (): array => [
            'event_type' => PerformanceEventType::WeighIn,
            'result' => PerformanceResult::NotApplicable,
            'duration_seconds' => null,
        ]);
    }

    public function derby(): static
    {
        return $this->state(fn (): array => ['event_type' => PerformanceEventType::Derby]);
    }

    public function sparring(): static
    {
        return $this->state(fn (): array => ['event_type' => PerformanceEventType::Sparring]);
    }

    public function unrated(): static
    {
        return $this->state(fn (): array => ['rating' => null]);
    }

    public function rating(int $stars): static
    {
        return $this->state(fn (): array => ['rating' => $stars]);
    }

    public function on(string $date): static
    {
        return $this->state(fn (): array => ['event_date' => $date]);
    }
}
