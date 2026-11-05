<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

use App\Enums\Sex;
use App\Livewire\Concerns\ChoosesShellByViewer;
use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Show extends Component
{
    use ChoosesShellByViewer;

    public Broodcock $broodcock;

    /**
     * The section on show. 'overview' is the pedigree - the reason anyone opens
     * a bird's page - and stays the default key so old links keep working.
     */
    #[Url(except: 'overview')]
    public string $tab = 'overview';

    public bool $confirmingDeletion = false;

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
        $this->authorize('update', $this->broodcock);
        $this->showForm = true;
    }

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('view', $broodcock);

        $this->broodcock = $broodcock;

        // A hen has no Performance tab; an old link to it opens the pedigree.
        if ($this->tab === 'performance' && ! $this->hasPerformance()) {
            $this->tab = 'overview';
        }
    }

    /** Hens do not fight, so only a broodcock has a performance record. */
    public function hasPerformance(): bool
    {
        return $this->broodcock->sex !== Sex::Female;
    }

    /**
     * The bird with everything the overview panel reads already loaded.
     * Model::shouldBeStrict() is on in development, so any relation the view
     * touches must appear here or the page throws rather than silently N+1.
     */
    #[Computed]
    public function bird(): Broodcock
    {
        return Broodcock::query()
            ->with([
                // Parents carry their own sire_id and dam_id so the
                // grandparents below can load in the same pass - one query per
                // generation, never one per ancestor.
                'sire:id,name,band_number,bloodline,sex,is_external,sire_id,dam_id',
                'dam:id,name,band_number,bloodline,sex,is_external,sire_id,dam_id',
                'sire.sire:id,name,band_number,bloodline,sex,is_external',
                'sire.dam:id,name,band_number,bloodline,sex,is_external',
                'dam.sire:id,name,band_number,bloodline,sex,is_external',
                'dam.dam:id,name,band_number,bloodline,sex,is_external',
                'pen:id,code,name',
                'primaryPhoto',
                // The gallery at the top of the page: the main photograph first.
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id'),
                'mortalityRecord',
            ])
            ->findOrFail($this->broodcock->id);
    }

    /**
     * Direct offspring, from either side of the pair.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function offspring(): Collection
    {
        return Broodcock::query()
            ->where(function ($query): void {
                $query->where('sire_id', $this->broodcock->id)
                    ->orWhere('dam_id', $this->broodcock->id);
            })
            ->orderByRaw('date_hatched is null')   // known dates first
            ->orderBy('date_hatched')
            ->get(['id', 'name', 'band_number', 'sex', 'status', 'date_hatched', 'bloodline']);
    }

    public function confirmDeletion(): void
    {
        $this->authorize('delete', $this->broodcock);

        $this->confirmingDeletion = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->broodcock);

        $name = $this->broodcock->name;

        // Soft delete - nothing is ever removed permanently from a system
        // whose selling point is a complete audit trail.
        DB::transaction(fn () => $this->broodcock->delete());

        session()->flash('success', "{$name} has been removed from the active records. The history is kept.");

        $this->redirectRoute('broodcocks.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.broodcocks.show')
            ->layout($this->viewerShell())
            ->title($this->bird->name);
    }
}
