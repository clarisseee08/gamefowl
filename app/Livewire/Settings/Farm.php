<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\FarmSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The farm's public identity, edited by the owner.
 *
 * WHAT THIS SCREEN REPLACED. Every field here was a GFMS_FARM_* environment
 * variable, which put the farm's own phone number behind a code change and a
 * deploy - and behind the one person on the project who can do either. The
 * owner is who the number belongs to and who knows when it changes.
 *
 * Nothing here is a record about a bird, so this sits outside the farm-records
 * cache entirely: saving forgets one dedicated key (see App\Support\FarmProfile)
 * rather than invalidating the catalogue, which has nothing to do with it.
 */
final class Farm extends Component
{
    public string $farm_name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $hours = '';

    public string $visitor_note = '';

    /** Set after a successful save so the page can say so. */
    public bool $saved = false;

    public function mount(): void
    {
        $this->authorize('update', FarmSetting::class);

        $setting = FarmSetting::current();

        $this->farm_name = $setting->farm_name ?? '';
        $this->phone = $setting->phone ?? '';
        $this->email = $setting->email ?? '';
        $this->address = $setting->address ?? '';
        $this->hours = $setting->hours ?? '';
        $this->visitor_note = $setting->visitor_note ?? '';
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            // The only required one. It is the page title, the sidebar and
            // every PDF footer; blank there is blank in all of them, whereas a
            // blank phone number simply removes a row from the Visit section.
            'farm_name' => ['required', 'string', 'max:120'],

            /*
             * A phone number is a string, not a number, and it is not validated
             * beyond a length. "+639123456789", "0912 345 6789" and
             * "(033) 396 1234" are all things a farm legitimately writes, and a
             * regex that accepted the first two and refused the third would be
             * a rule the owner has to fight rather than one that helps.
             */
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:120'],
            'visitor_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'farm_name.required' => 'The farm needs a name - it appears on every page and every report.',
            'email.email' => 'That does not look like an email address.',
        ];
    }

    public function updated(): void
    {
        // Any edit after a save means the confirmation is about the previous
        // state and is now lying.
        $this->saved = false;
    }

    public function save(): void
    {
        $this->authorize('update', FarmSetting::class);

        $validated = $this->validate();

        /*
         * updateOrCreate on the existing row rather than save() on the instance
         * from mount(): between opening this page and pressing save, that
         * instance may be the unsaved fallback FarmSetting::current() hands
         * back when the table is empty. Writing through the query builder means
         * a missing row is created and an existing one is updated, and either
         * way there is exactly one.
         */
        $setting = FarmSetting::query()->oldest('id')->first();

        if ($setting === null) {
            FarmSetting::query()->create($validated);
        } else {
            $setting->update($validated);
        }

        // The cached copy is refreshed by the model's saved event, not from
        // here, so that a seeder or a tinker session editing the row refreshes
        // it too - and so that the sidebar on this very response already shows
        // the new farm name.

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.settings.farm')->title('Farm profile');
    }
}
