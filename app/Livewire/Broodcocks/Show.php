<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

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

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    public bool $confirmingDeletion = false;

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('view', $broodcock);

        $this->broodcock = $broodcock;
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
                'sire:id,name,band_number,bloodline,sex',
                'dam:id,name,band_number,bloodline,sex',
                'pen:id,code,name',
                'primaryPhoto',
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
            ->layout($this->viewerShell());
    }
}
