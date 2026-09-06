<?php

declare(strict_types=1);

namespace Tests\Feature\Broodcocks;

use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Livewire\Broodcocks\Form;
use App\Models\Broodcock;
use App\Models\Pen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Creating and editing a bird.
 *
 * This existed nowhere: the broodcock suite covered authorization and photo
 * handling, but nothing actually filled the form in and saved it. So the whole
 * write path — the field set, the empty-string-to-null normalisation, and
 * whether an edit preserves the columns the form no longer shows — was passing
 * on the strength of not being tested.
 */
final class BroodcockFormTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    public function test_staff_can_create_a_bird(): void
    {
        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('name', 'Haring Agila')
            ->set('band_number', 'SW-9001')
            ->set('bloodline', 'Sweater')
            ->set('class', BroodcockClass::ClassA->value)
            ->set('sex', Sex::Male->value)
            ->set('status', BroodcockStatus::Active->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('broodcocks', [
            'name' => 'Haring Agila',
            'band_number' => 'SW-9001',
            'bloodline' => 'Sweater',
        ]);
    }

    public function test_staff_can_edit_a_bird(): void
    {
        $bird = Broodcock::factory()->create([
            'name' => 'Old Name',
            'bloodline' => 'Kelso',
        ]);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $bird])
            ->set('name', 'New Name')
            ->set('bloodline', 'Hatch')
            ->call('save')
            ->assertHasNoErrors();

        $bird->refresh();

        $this->assertSame('New Name', $bird->name);
        $this->assertSame('Hatch', $bird->bloodline);
    }

    /**
     * The form no longer shows Breed or Pen, but the columns still hold data.
     * An edit must leave them alone rather than blanking them, which is exactly
     * what would happen if they were still in the validated payload as nulls.
     */
    public function test_editing_a_bird_does_not_wipe_the_columns_the_form_no_longer_shows(): void
    {
        $pen = Pen::factory()->create();
        $bird = Broodcock::factory()->create([
            'name' => 'Kept Intact',
            'breed' => 'Asil',
            'pen_id' => $pen->id,
        ]);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $bird])
            ->set('name', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $bird->refresh();

        $this->assertSame('Renamed', $bird->name);
        $this->assertSame('Asil', $bird->breed, 'The hidden breed value was wiped by an edit.');
        $this->assertSame($pen->id, $bird->pen_id, 'The hidden pen assignment was wiped by an edit.');
    }

    public function test_the_edit_form_loads_the_existing_values(): void
    {
        $bird = Broodcock::factory()->create([
            'name' => 'Loaded Bird',
            'bloodline' => 'Roundhead',
            'band_number' => 'RH-1234',
        ]);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $bird])
            ->assertSet('name', 'Loaded Bird')
            ->assertSet('bloodline', 'Roundhead')
            ->assertSet('band_number', 'RH-1234');
    }

    public function test_a_name_is_required(): void
    {
        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('name', '')
            ->set('class', BroodcockClass::Ordinary->value)
            ->set('sex', Sex::Male->value)
            ->set('status', BroodcockStatus::Active->value)
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_a_duplicate_band_number_is_rejected(): void
    {
        Broodcock::factory()->create(['band_number' => 'SW-4001']);

        Livewire::actingAs($this->staff())
            ->test(Form::class)
            ->set('name', 'Second Bird')
            ->set('band_number', 'SW-4001')
            ->set('class', BroodcockClass::Ordinary->value)
            ->set('sex', Sex::Male->value)
            ->set('status', BroodcockStatus::Active->value)
            ->call('save')
            ->assertHasErrors('band_number');
    }

    public function test_editing_a_bird_keeps_its_own_band_number(): void
    {
        $bird = Broodcock::factory()->create(['band_number' => 'SW-4001']);

        Livewire::actingAs($this->staff())
            ->test(Form::class, ['broodcock' => $bird])
            ->set('name', 'Same Band')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('SW-4001', $bird->refresh()->band_number);
    }
}
