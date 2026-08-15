<?php

declare(strict_types=1);

namespace App\Livewire\Mortality;

use App\Actions\Mortality\RecordMortality;
use App\Enums\BroodcockStatus;
use App\Http\Requests\StoreMortalityRecordRequest;
use App\Models\Broodcock;
use App\Models\MortalityRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Recording a death.
 *
 * Two gates run here: MortalityRecordPolicy::create() decides whether the
 * screen may be opened at all, and BroodcockPolicy::recordMortality() decides
 * whether the chosen bird may be marked dead. Neither is inferable from the
 * markup - both are checked server-side on save.
 */
#[Title('Record a Death')]
final class Form extends Component
{
    public string $broodcock_id = '';

    public string $date_of_death = '';

    public string $cause_of_death = '';

    public ?string $disposal_method = null;

    public ?string $remarks = null;

    /** Narrows the bird list - a farm with hundreds of birds needs it. */
    public string $birdSearch = '';

    /** Set once the user has filled the form and pressed Record. */
    public bool $confirming = false;

    public function mount(?Broodcock $broodcock = null): void
    {
        $this->authorize('create', MortalityRecord::class);

        if ($broodcock?->exists) {
            $this->authorize('recordMortality', $broodcock);
            $this->broodcock_id = (string) $broodcock->id;
        }
    }

    /** Changing the bird changes the earliest allowed date, so re-check. */
    public function updatedBroodcockId(): void
    {
        $this->confirming = false;
        $this->resetValidation();
        unset($this->selectedBird);
    }

    public function updatedBirdSearch(): void
    {
        unset($this->eligibleBirds);
    }

    /**
     * Step 1: validate, then ask for confirmation by name. Recording a death
     * is irreversible-feeling for the person doing it, so it is never one
     * click - but the confirmation is only shown once the data is known good.
     */
    public function review(): void
    {
        $this->validateForm();

        // Refuse a bird the policy would not accept before showing a dialog
        // that promises to record its death. save() checks this again - never
        // trust that this step ran.
        $this->authorize('recordMortality', Broodcock::findOrFail((int) $this->broodcock_id));

        $this->confirming = true;
    }

    public function cancelConfirmation(): void
    {
        $this->confirming = false;
    }

    /** Step 2: write it. */
    public function save(RecordMortality $action): void
    {
        $this->authorize('create', MortalityRecord::class);

        // Validate BEFORE authorizing the bird: if another user recorded this
        // same death a moment ago, the unique rule explains that in words,
        // which is kinder than the 403 the policy would otherwise produce.
        $data = $this->validateForm();

        $broodcock = Broodcock::findOrFail((int) $this->broodcock_id);

        // The real gate. Refuses customers, deactivated accounts, and any bird
        // that is already deceased.
        $this->authorize('recordMortality', $broodcock);

        /** @var User $actor */
        $actor = Auth::user();

        $action->handle($broodcock, $data, $actor);

        session()->flash(
            'success',
            "The death of {$broodcock->displayName()} has been recorded. The bird is now marked as deceased."
        );

        $this->redirectRoute('mortality.index', navigate: true);
    }

    /** @return array<string, mixed> */
    private function validateForm(): array
    {
        $broodcock = $this->selectedBird;

        return $this->validate(
            StoreMortalityRecordRequest::rulesFor($broodcock),
            StoreMortalityRecordRequest::messagesFor($broodcock),
            StoreMortalityRecordRequest::attributesFor(),
        );
    }

    // -----------------------------------------------------------------
    // Options
    // -----------------------------------------------------------------

    /**
     * Birds that may still be marked dead: on the farm, and with no mortality
     * record already. A deceased bird is not offered at all - the policy would
     * refuse it, and offering a choice that is always rejected is not a choice.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function eligibleBirds(): Collection
    {
        return Broodcock::query()
            ->select(['id', 'name', 'band_number', 'date_hatched', 'status'])
            ->whereNot('status', BroodcockStatus::Deceased->value)
            ->whereDoesntHave('mortalityRecord')
            ->when($this->birdSearch !== '', fn (Builder $query) => $query->search($this->birdSearch))
            ->orderBy('name')
            ->limit(200)
            ->get();
    }

    #[Computed]
    public function selectedBird(): ?Broodcock
    {
        if ($this->broodcock_id === '' || ! ctype_digit($this->broodcock_id)) {
            return null;
        }

        return Broodcock::query()
            ->select(['id', 'name', 'band_number', 'date_hatched', 'status'])
            ->find((int) $this->broodcock_id);
    }

    /**
     * Suggestions only - the field stays free text, farms have odd causes.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function causeSuggestions(): array
    {
        return [
            'Disease', 'Injury', 'Predator attack', 'Old age',
            'Heat stress', 'Fighting', 'Unknown',
        ];
    }

    /** @return array<int, string> */
    #[Computed]
    public function disposalSuggestions(): array
    {
        return ['Buried', 'Burned', 'Composted', 'Sent for laboratory examination'];
    }

    public function render(): View
    {
        return view('livewire.mortality.form');
    }
}
