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

    // -----------------------------------------------------------------
    // One upload path, one set of rules
    //
    // This form and the dedicated uploader on a bird's page store photos
    // through the same StorePhoto action, so they must also VALIDATE the same
    // way. They did not: the rules here were written as literals (max:10,
    // max:4096) and had no `mimes` rule at all.
    // -----------------------------------------------------------------

    /**
     * The size limit is farm policy in config, not a number in a component.
     *
     * With the limit restated as `max:4096`, lowering GFMS_PHOTO_MAX_KB changed
     * what the uploader accepted and left this form accepting 4 MB regardless.
     */
    public function test_the_size_limit_comes_from_config(): void
    {
        config(['gfms.photos.max_kilobytes' => 100]);
        Storage::fake('local');

        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        // Comfortably under the old 4 MB literal, over the configured limit.
        $component
            ->set('photos', [UploadedFile::fake()->image('medium.jpg')->size(500)])
            ->call('save')
            ->assertHasErrors('photos.*');

        $this->assertDatabaseMissing('broodcocks', ['name' => 'Bagwis']);
    }

    /** Likewise the per-bird cap, which this form did not consult at all. */
    public function test_the_per_bird_cap_comes_from_config(): void
    {
        config(['gfms.photos.max_per_broodcock' => 2]);
        Storage::fake('local');

        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        $component
            ->set('photos', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ])
            ->call('save')
            ->assertHasErrors('photos');

        $this->assertDatabaseMissing('broodcocks', ['name' => 'Bagwis']);
    }

    /**
     * An SVG passes Laravel's `image` rule, which is why `mimes` exists.
     *
     * The uploader pinned the accepted types; this form did not, so the same
     * file was accepted on one screen and refused on the next. Storing one is
     * not currently exploitable - BroodcockPhotoController sends nosniff and
     * labels unknown extensions image/jpeg - but "the other layer catches it"
     * is not a rule, it is a coincidence waiting to be refactored away.
     */
    public function test_a_file_type_the_uploader_refuses_is_refused_here_too(): void
    {
        Storage::fake('local');

        $component = Livewire::actingAs($this->staff())->test(Form::class);

        foreach ($this->validBird() as $field => $value) {
            $component->set($field, $value);
        }

        $component
            ->set('photos', [UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml')])
            ->call('save')
            ->assertHasErrors('photos.*');

        $this->assertDatabaseMissing('broodcocks', ['name' => 'Bagwis']);
    }
}
