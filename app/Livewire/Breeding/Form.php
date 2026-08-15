<?php

declare(strict_types=1);

namespace App\Livewire\Breeding;

use App\Enums\Sex;
use App\Http\Requests\StoreBreedingRecordRequest;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Form extends Component
{
    public ?BreedingRecord $record = null;

    public ?string $sire_id = null;

    public ?string $dam_id = null;

    public ?string $mating_date = null;

    public int $eggs_set = 0;

    public int $eggs_fertile = 0;

    public int $eggs_hatched = 0;

    public int $offspring_count = 0;

    public ?string $notes = null;

    public function mount(?BreedingRecord $record = null): void
    {
        if ($record?->exists) {
            $this->authorize('update', $record);
            $this->record = $record;

            $this->fill([
                'sire_id' => (string) $record->sire_id,
                'dam_id' => (string) $record->dam_id,
                'mating_date' => $record->mating_date->toDateString(),
                'eggs_set' => $record->eggs_set,
                'eggs_fertile' => $record->eggs_fertile,
                'eggs_hatched' => $record->eggs_hatched,
                'offspring_count' => $record->offspring_count,
                'notes' => $record->notes,
            ]);

            return;
        }

        $this->authorize('create', BreedingRecord::class);
        $this->mating_date = today()->toDateString();
    }

    public function isEditing(): bool
    {
        return $this->record?->exists ?? false;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return StoreBreedingRecordRequest::rulesFor($this->record);
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return StoreBreedingRecordRequest::attributeNames();
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return StoreBreedingRecordRequest::messageOverrides();
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    /**
     * Live preview of the rates, so the person entering the numbers sees
     * immediately whether they look sensible. These are never stored.
     *
     * @return array{fertility: float|null, hatch: float|null}
     */
    #[Computed]
    public function previewRates(): array
    {
        return [
            'fertility' => $this->eggs_set > 0
                ? round(($this->eggs_fertile / $this->eggs_set) * 100, 1)
                : null,
            'hatch' => $this->eggs_fertile > 0
                ? round(($this->eggs_hatched / $this->eggs_fertile) * 100, 1)
                : null,
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        // Cross-field rule that needs the parent models loaded.
        $validator = validator([], []);
        StoreBreedingRecordRequest::validateMatingDateAgainstParents($validator, $data);

        if ($validator->errors()->isNotEmpty()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $data['recorded_by'] = auth()->id();

        $saved = DB::transaction(function () use ($data): BreedingRecord {
            if ($this->isEditing()) {
                $this->record->update($data);

                return $this->record;
            }

            return BreedingRecord::create($data);
        });

        session()->flash('success', $this->isEditing()
            ? 'The breeding record has been updated.'
            : 'The breeding record has been saved.');

        $this->redirectRoute('breeding.show', $saved, navigate: true);
    }

    /** @return Collection<int, Broodcock> */
    #[Computed]
    public function sires(): Collection
    {
        return $this->parentOptions(Sex::Male, $this->sire_id);
    }

    /** @return Collection<int, Broodcock> */
    #[Computed]
    public function dams(): Collection
    {
        return $this->parentOptions(Sex::Female, $this->dam_id);
    }

    /** @return Collection<int, Broodcock> */
    private function parentOptions(Sex $sex, ?string $selected): Collection
    {
        return Broodcock::query()
            ->where('sex', $sex->value)
            ->where(function ($query) use ($selected): void {
                $query->breedingEligible();

                // Keep the currently-selected bird in the list even if it has
                // since been retired, or editing an old record would silently
                // blank its parent.
                if ($selected !== null && $selected !== '') {
                    $query->orWhere('id', $selected);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'band_number', 'bloodline']);
    }

    public function render(): View
    {
        return view('livewire.breeding.form');
    }
}
