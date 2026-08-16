<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Livewire\Broodcocks\Form;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Attaching photos while ADDING a bird.
 *
 * The interesting constraint: a photo row needs a broodcock_id, so on create
 * the bird cannot exist when the file is chosen. The files are held on the
 * component and attached the moment the insert commits.
 */
final class BroodcockFormPhotosTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    /** @return array<string, mixed> */
    private function validBird(): array
    {
        return [
            'name' => 'Bagwis',
            'sex' => 'male',
            'class' => 'class_a',
            'status' => 'active',
        ];
    }

    public function test_photos_chosen_on_the_add_form_are_attached_to_the_new_bird(): void
    {
        Storage::fake('local');

        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        $component
            ->set('photos', [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $bird = Broodcock::where('name', 'Bagwis')->firstOrFail();

        $this->assertSame(2, BroodcockPhoto::where('broodcock_id', $bird->id)->count());
    }

    /** Photos are optional; the bird must save perfectly well without any. */
    public function test_a_bird_can_be_added_with_no_photos(): void
    {
        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', ['name' => 'Bagwis']);
    }

    public function test_an_oversized_photo_blocks_the_save(): void
    {
        Storage::fake('local');

        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        $component
            ->set('photos', [UploadedFile::fake()->image('huge.jpg')->size(5000)])
            ->call('save')
            ->assertHasErrors('photos.*');

        // The bird must NOT be created when its photos fail validation, or a
        // keeper ends up with a half-entered record and no idea it saved.
        $this->assertDatabaseMissing('broodcocks', ['name' => 'Bagwis']);
    }

    public function test_photos_added_while_editing_append_to_the_existing_set(): void
    {
        Storage::fake('local');

        $bird = Broodcock::factory()->create();
        BroodcockPhoto::factory()->for($bird)->create();

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $bird])
            ->set('photos', [UploadedFile::fake()->image('extra.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, BroodcockPhoto::where('broodcock_id', $bird->id)->count());
    }
}
