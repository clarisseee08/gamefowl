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
    /** Rendered inside the list's form dialog rather than as a page of its own. */
    public bool $inDialog = false;

    public string $broodcock_id = '';

    public string $date_of_death = '';

    public string $cause_of_death = '';

    public ?string $disposal_method = null;

    /**
     * What the Cause of Death dropdown shows: one of causeOptions()'s keys, or
     * OTHER. Only cause_of_death is validated and saved - picking an option
     * copies its short name there, and OTHER clears it for the text box.
     */
    public string $cause_choice = '';

    /** The same for How Was the Bird Disposed Of? */
    public string $disposal_choice = '';

    /** The dropdown value that opens a text box for the user's own answer. */
    public const OTHER = '__other';

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

        // The real gate. Refuses deactivated accounts, and any bird
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
            ->select(['id', 'name', 'band_number', 'bloodline', 'date_hatched', 'status'])
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

        // primaryPhoto, sex and is_external for the photo preview under the picker.
        return Broodcock::query()
            ->select(['id', 'name', 'band_number', 'bloodline', 'date_hatched', 'status', 'sex', 'is_external'])
            ->with('primaryPhoto')
            ->find((int) $this->broodcock_id);
    }

    public function updatedCauseChoice(string $choice): void
    {
        $this->cause_of_death = $choice === self::OTHER ? '' : $choice;
    }

    public function updatedDisposalChoice(string $choice): void
    {
        $this->disposal_method = match ($choice) {
            '' => null,
            self::OTHER => '',
            default => $choice,
        };
    }

    /**
     * The causes the farm records most, each with what it covers, so Injury
     * and Fighting are not used for the same death. The key is what is
     * saved; the explanation is only shown. Anything else goes under Other.
     *
     * @return array<string, string>
     */
    public static function causeOptions(): array
    {
        return [
            'Disease' => 'an illness or infection',
            'Injury' => 'an accident on the farm, not a fight',
            'Predator attack' => 'killed by a dog, snake, hawk or other animal',
            'Old age' => 'no illness or injury',
            'Heat stress' => 'overheated in hot weather',
            'Fighting' => 'wounds from a fight or sparring',
            'Unknown' => 'the cause could not be found',
        ];
    }

    /**
     * How a dead bird is disposed of. "Composted" was dropped: it read too
     * much like Buried. The key is what is saved.
     *
     * @return array<string, string>
     */
    public static function disposalOptions(): array
    {
        return [
            'Buried' => 'in the ground, on or near the farm',
            'Burned' => 'in a fire on the farm',
            'Discarded' => 'thrown out with farm waste',
            'Sent for laboratory examination' => 'to find the cause of death',
        ];
    }

    public function render(): View
    {
        return view('livewire.mortality.form', ['otherChoice' => self::OTHER]);
    }
}
