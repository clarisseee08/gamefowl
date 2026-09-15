<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FarmSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FarmSetting>
 *
 * There is only ever one farm_settings row, and the create migration inserts
 * it - so unlike every other factory here, this one is not for building a
 * fixture out of nothing. It is for a test that needs the row to hold
 * something specific, via FarmSetting::current()->update(...) or, on a
 * database where the row has been removed, a plain create().
 */
final class FarmSettingFactory extends Factory
{
    protected $model = FarmSetting::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'farm_name' => fake()->company().' Game Farm',
            'address' => fake()->city().', Iloilo',
            'phone' => '+639'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'hours' => 'Monday to Saturday, 8AM to 5PM',
            'visitor_note' => null,
        ];
    }

    /** Every optional field blank, to exercise the "omit the row" behaviour. */
    public function withoutContactDetails(): static
    {
        return $this->state(fn (): array => [
            'address' => null,
            'phone' => null,
            'email' => null,
            'hours' => null,
            'visitor_note' => null,
        ]);
    }
}
