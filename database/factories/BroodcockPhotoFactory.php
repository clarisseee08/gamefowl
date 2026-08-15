<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BroodcockPhoto>
 */
class BroodcockPhotoFactory extends Factory
{
    protected $model = BroodcockPhoto::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'broodcock_id' => Broodcock::factory(),
            // Mirrors what StorePhoto writes: a per-bird prefix and a random
            // name, never anything the uploader chose.
            'path' => fn (array $attributes): string => sprintf(
                'broodcocks/%s/%s.jpg',
                $attributes['broodcock_id'] instanceof Broodcock
                    ? $attributes['broodcock_id']->id
                    : $attributes['broodcock_id'],
                Str::uuid()->toString(),
            ),
            'disk' => (string) config('gfms.photo_disk'),
            'caption' => fake()->optional(0.5)->sentence(4),
            'is_primary' => false,
            'uploaded_by' => User::factory()->staff(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    /** Named forBroodcock() rather than for() - Factory::for() already exists. */
    public function forBroodcock(Broodcock $broodcock): static
    {
        return $this->state(fn () => ['broodcock_id' => $broodcock->id]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn () => ['uploaded_by' => $user->id]);
    }

    public function withCaption(string $caption): static
    {
        return $this->state(fn () => ['caption' => $caption]);
    }

    public function onDisk(string $disk): static
    {
        return $this->state(fn () => ['disk' => $disk]);
    }
}
