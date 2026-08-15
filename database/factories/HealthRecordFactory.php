<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HealthRecordType;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
{
    protected $model = HealthRecord::class;

    /** Real product names, so seeded data reads like a farm's book rather than lorem ipsum. */
    private const PRODUCTS = [
        'Newcastle Disease vaccine',
        'Fowl Pox vaccine',
        'Infectious Bronchitis vaccine',
        'Levamisole dewormer',
        'Piperazine dewormer',
        'Amprolium',
        'Enrofloxacin',
        'Vitamin B-complex',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $type = fake()->randomElement(HealthRecordType::cases());
        $checkup = fake()->dateTimeBetween('-1 year', 'today');

        return [
            // Wrapped in a closure so the related factory is only resolved when
            // the caller has not supplied a bird of their own.
            'broodcock_id' => fn () => Broodcock::factory(),
            'record_type' => $type,
            'product_name' => fake()->randomElement(self::PRODUCTS),
            'dosage' => fake()->randomElement(['0.25 ml', '0.5 ml', '1 ml', '1 tablet', '2 ml']),
            'checkup_date' => $checkup->format('Y-m-d'),
            // Only the recurring types normally carry a follow-up date, which
            // keeps generated data consistent with expectsNextDueDate().
            'next_due_date' => $type->expectsNextDueDate()
                ? fake()->dateTimeBetween($checkup, '+6 months')->format('Y-m-d')
                : null,
            'condition' => fake()->randomElement(['Healthy', 'Alert and active', 'Mild cough', 'Slight limp', 'Recovering']),
            'remarks' => fake()->boolean(40) ? fake()->sentence() : null,
            'recorded_by' => fn () => User::factory()->staff(),
        ];
    }

    /**
     * Force a record type, keeping the follow-up date consistent with
     * expectsNextDueDate() rather than inheriting whatever the random type
     * in definition() happened to produce.
     */
    public function ofType(HealthRecordType $type): static
    {
        return $this->state(function (array $attributes) use ($type): array {
            $checkup = $attributes['checkup_date'];

            return [
                'record_type' => $type,
                'next_due_date' => $type->expectsNextDueDate()
                    ? fake()->dateTimeBetween($checkup, $checkup.' +6 months')->format('Y-m-d')
                    : null,
            ];
        });
    }

    public function vaccination(): static
    {
        return $this->state(fn () => [
            'record_type' => HealthRecordType::Vaccination,
            'product_name' => 'Newcastle Disease vaccine',
        ]);
    }

    public function deworming(): static
    {
        return $this->state(fn () => [
            'record_type' => HealthRecordType::Deworming,
            'product_name' => 'Levamisole dewormer',
        ]);
    }

    /** A follow-up whose due date has already passed. */
    public function overdue(int $daysLate = 10): static
    {
        return $this->state(fn () => [
            'record_type' => HealthRecordType::Vaccination,
            'checkup_date' => today()->subDays($daysLate + 90)->toDateString(),
            'next_due_date' => today()->subDays($daysLate)->toDateString(),
        ]);
    }

    /** A follow-up falling due inside the warning window. */
    public function dueSoon(int $inDays = 7): static
    {
        return $this->state(fn () => [
            'record_type' => HealthRecordType::Vaccination,
            'checkup_date' => today()->subDays(60)->toDateString(),
            'next_due_date' => today()->addDays($inDays)->toDateString(),
        ]);
    }

    /** A one-off treatment with nothing scheduled after it. */
    public function withoutFollowUp(): static
    {
        return $this->state(fn () => [
            'record_type' => HealthRecordType::Checkup,
            'next_due_date' => null,
        ]);
    }
}
