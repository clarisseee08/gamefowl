<?php

declare(strict_types=1);

namespace App\Livewire\Breeding;

use App\Actions\Broodcocks\ResolveExternalParent;
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

    /**
     * The value the parent dropdowns use for "not one of ours".
     *
     * A sentinel rather than a real id: the dropdown has to express three
     * things in one control - no parent recorded, a bird on the farm, and a
     * bird the farm does not own.
     */
    public const OFF_LIST = 'external';

    /*
     * What the dropdown is showing, which is not the same as which bird was
     * chosen - it also holds the sentinel above. sire_id stays the foreign key.
     */
    public ?string $sire_choice = null;

    public ?string $dam_choice = null;

    /**
     * An outside parent - a bird the farm does not own, typed as a name
     * instead of chosen from the list. Borrowed and visiting hens are normal
     * practice here, and before this they could not be recorded at all.
     */
    public bool $sire_is_external = false;

    public ?string $sire_external_name = null;

    public ?string $sire_external_bloodline = null;

    public bool $dam_is_external = false;

    public ?string $dam_external_name = null;

    public ?string $dam_external_bloodline = null;

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
                'sire_choice' => (string) $record->sire_id,
                'dam_choice' => (string) $record->dam_id,
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
        return StoreBreedingRecordRequest::rulesFor(
            $this->record,
            $this->sire_is_external,
            $this->dam_is_external,
        );
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

    public function updatedSireChoice(?string $value): void
    {
        $this->applyParentChoice('sire', $value);
    }

    public function updatedDamChoice(?string $value): void
    {
        $this->applyParentChoice('dam', $value);
    }

    /**
     * Translate the dropdown back into the two things the save path reads.
     *
     * sire_id and sire_is_external are what validation and
     * ResolveExternalParent actually consult; the dropdown is one control
     * expressing three states, and nothing below this method knows it changed.
     *
     * Clearing the typed name is the point rather than tidiness: pick the
     * off-list option, type a name, then change your mind and choose a farm
     * bird, and without this the name sits in a property the save path is no
     * longer reading - until a later edit sets the flag again and quietly
     * registers a bird nobody asked for.
     */
    private function applyParentChoice(string $role, ?string $value): void
    {
        $isOffList = $value === self::OFF_LIST;

        $this->{$role.'_is_external'} = $isOffList;
        $this->{$role.'_id'} = ($isOffList || $value === null || $value === '') ? null : $value;

        if (! $isOffList) {
            $this->{$role.'_external_name'} = null;
            $this->{$role.'_external_bloodline'} = null;
        }
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

        // Cross-field rule that needs the parent models loaded. An outside
        // parent has no row yet, so its id is still null here and the check
        // skips it - which is right, because an outside bird has no hatch
        // date on file to compare the mating against either.
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
            // Outside parents are turned into real broodcock rows here, in the
            // same transaction as the record itself. Two reasons: sire_id and
            // dam_id stay foreign keys, so the pedigree tree keeps the branch
            // above that bird; and a save that fails leaves no orphan bird
            // behind.
            if ($this->sire_is_external) {
                $data['sire_id'] = $this->resolveExternalParent(
                    Sex::Male,
                    (string) $this->sire_external_name,
                    $this->sire_external_bloodline,
                );
            }

            if ($this->dam_is_external) {
                $data['dam_id'] = $this->resolveExternalParent(
                    Sex::Female,
                    (string) $this->dam_external_name,
                    $this->dam_external_bloodline,
                );
            }

            // Form-only helpers; they are not columns on breeding_records.
            unset(
                $data['sire_is_external'], $data['dam_is_external'],
                $data['sire_external_name'], $data['dam_external_name'],
                $data['sire_external_bloodline'], $data['dam_external_bloodline'],
            );

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

    /** Delegates to the shared action - see ResolveExternalParent for why. */
    private function resolveExternalParent(Sex $sex, string $name, ?string $bloodline): int
    {
        return (int) app(ResolveExternalParent::class)->handle($sex, $name, $bloodline)->id;
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
