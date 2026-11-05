<?php

declare(strict_types=1);

namespace App\Livewire\Breeding;

use App\Actions\Breeding\GenerateOffspring;
use App\Enums\BroodcockClass;
use App\Enums\Sex;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

final class Show extends Component
{
    public BreedingRecord $record;

    // Offspring generation form state.
    public bool $generating = false;

    public int $generateCount = 1;

    public string $generateSex = '';

    public ?string $generateDateHatched = null;

    public ?string $generatePenId = null;

    /**
     * The edit form, open in a side sheet over this page rather than on a page
     * of its own (the owner's decision). The form component is unchanged.
     */
    public bool $showForm = false;

    /** Closing the dialog re-keys the form, so it reopens as the record stands. */
    public int $formNonce = 0;

    public function updatedShowForm(bool $open): void
    {
        if (! $open) {
            $this->formNonce++;
        }
    }

    /** Called by the dialog when a form that was typed in is closed: start the next one blank. */
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->updatedShowForm(false);
    }

    public function openForm(): void
    {
        $this->authorize('update', $this->record);
        $this->showForm = true;
    }

    public function mount(BreedingRecord $record): void
    {
        $this->authorize('view', $record);

        $this->record = $record;
        $this->generateSex = Sex::Male->value;
    }

    #[Computed]
    public function breeding(): BreedingRecord
    {
        return BreedingRecord::query()
            // withTrashed() because deleting a bird keeps its breeding history:
            // without it a deleted parent comes back null and this page fails.
            ->with([
                'sire' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'band_number', 'bloodline', 'breed', 'sex', 'deleted_at'])->with('primaryPhoto'),
                'dam' => fn ($query) => $query->withTrashed()->select(['id', 'name', 'band_number', 'bloodline', 'breed', 'sex', 'deleted_at'])->with('primaryPhoto'),
                'recordedBy:id,full_name',
            ])
            ->findOrFail($this->record->id);
    }

    /**
     * Chicks already registered from this mating.
     *
     * Matched on the parent pair rather than a direct FK: `broodcocks` has no
     * breeding_record_id column, because a bird's parentage is a property of
     * the bird, not of one particular mating entry.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function offspring(): Collection
    {
        return Broodcock::query()
            ->where('sire_id', $this->record->sire_id)
            ->where('dam_id', $this->record->dam_id)
            // The photo too, for the hover card on each chick's row.
            ->with('primaryPhoto')
            ->orderBy('date_hatched')
            // bloodline is selected because each row carries a band tag, and
            // BandTag resolves its colour from the bloodline. It is a column on
            // this table, so this adds no query and no join - but leaving it out
            // throws MissingAttributeException rather than rendering a grey tag,
            // which is how the test caught it.
            ->get(['id', 'name', 'band_number', 'bloodline', 'sex', 'status', 'date_hatched']);
    }

    /** @return Collection<int, Pen> */
    #[Computed]
    public function pens(): Collection
    {
        return Pen::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function startGenerating(): void
    {
        $this->authorize('generateOffspring', $this->record);

        $this->generateCount = max(1, $this->record->unregisteredOffspring());
        $this->generateDateHatched = $this->record->mating_date->copy()->addDays(21)->toDateString();
        $this->generating = true;
    }

    public function generate(GenerateOffspring $action): void
    {
        $this->authorize('generateOffspring', $this->record);

        $this->validate([
            'generateCount' => ['required', 'integer', 'min:1', 'max:'.max(1, $this->record->unregisteredOffspring())],
            'generateSex' => ['required', Rule::enum(Sex::class)],
            'generateDateHatched' => ['nullable', 'date', 'before_or_equal:today'],
            'generatePenId' => ['nullable', 'integer', 'exists:pens,id'],
        ], [
            'generateCount.max' => 'You cannot register more chicks than this hatch produced.',
        ]);

        try {
            $created = $action->handle($this->record, $this->generateCount, [
                'sex' => Sex::from($this->generateSex),
                'date_hatched' => $this->generateDateHatched,
                'pen_id' => $this->generatePenId ?: null,
                'class' => BroodcockClass::Ordinary,
            ]);
        } catch (RuntimeException $e) {
            $this->addError('generateCount', $e->getMessage());

            return;
        }

        $this->record->refresh();
        $this->generating = false;

        // Recomputed values must be dropped or the page would show stale
        // counts until the next full request.
        unset($this->breeding, $this->offspring);

        $count = $created->count();

        // A toast, not a session flash: this action does not redirect, so a
        // flash would not appear here and would surface stale on the next page.
        $this->dispatch('toast', message: $count === 1
            ? '1 chick has been registered with its sire and dam already filled in.'
            : "{$count} chicks have been registered with their sire and dam already filled in.", tone: 'ok');
    }

    public function render(): View
    {
        return view('livewire.breeding.show')
            ->title(($this->breeding->sire?->name ?? 'Bird removed').' × '.($this->breeding->dam?->name ?? 'Bird removed'));
    }
}
