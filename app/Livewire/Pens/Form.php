<?php

declare(strict_types=1);

namespace App\Livewire\Pens;

use App\Http\Requests\StorePenRequest;
use App\Http\Requests\UpdatePenRequest;
use App\Models\Pen;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Create or edit a pen.
 *
 * One component serves both screens: `$penId` is null when adding and set
 * when editing. Validation rules are pulled from the Form Requests rather
 * than restated here, so there is exactly one definition of what a valid pen
 * looks like whether the write arrives from Livewire or from a plain POST.
 */
final class Form extends Component
{
    public ?int $penId = null;

    public string $code = '';

    public string $name = '';

    public string $location = '';

    public ?int $capacity = 0;

    public string $notes = '';

    public function mount(?Pen $pen = null): void
    {
        if ($pen === null || ! $pen->exists) {
            $this->authorize('create', Pen::class);

            return;
        }

        $this->authorize('update', $pen);

        $this->penId = $pen->id;
        $this->code = $pen->code;
        $this->name = $pen->name;
        $this->location = (string) $pen->location;
        $this->capacity = $pen->capacity;
        $this->notes = (string) $pen->notes;
    }

    public function isEditing(): bool
    {
        return $this->penId !== null;
    }

    /**
     * How many birds are already in the pen being edited. Used to warn when a
     * capacity is being set below what the pen currently holds - a warning,
     * not a block, because the birds are physically there either way.
     */
    #[Computed]
    public function currentOccupancy(): int
    {
        if ($this->penId === null) {
            return 0;
        }

        $pen = Pen::query()->withCount('broodcocks')->find($this->penId);

        return $pen?->occupancy() ?? 0;
    }

    public function save(): void
    {
        $pen = $this->penId === null ? null : Pen::query()->findOrFail($this->penId);

        if ($pen instanceof Pen) {
            $this->authorize('update', $pen);
        } else {
            $this->authorize('create', Pen::class);
        }

        $validated = $this->validate(
            $pen instanceof Pen
                ? UpdatePenRequest::rulesFor($pen->id)
                : StorePenRequest::rulesFor(),
            StorePenRequest::messagesFor(),
            StorePenRequest::attributesFor(),
        );

        // Blank optional text is stored as NULL, not as an empty string, so
        // "no location recorded" has exactly one representation.
        $validated['location'] = $this->blankToNull($validated['location'] ?? null);
        $validated['notes'] = $this->blankToNull($validated['notes'] ?? null);
        $validated['capacity'] = (int) $validated['capacity'];

        // The activity-log entry is written by a model event, so this is a
        // two-table write and belongs inside a transaction.
        $saved = DB::transaction(function () use ($pen, $validated): Pen {
            if ($pen instanceof Pen) {
                $pen->update($validated);

                return $pen;
            }

            return Pen::create($validated);
        });

        session()->flash('success', $pen instanceof Pen
            ? "Pen {$saved->code} was saved."
            : "Pen {$saved->code} was added.");

        $this->redirectRoute('pens.show', ['pen' => $saved->id], navigate: true);
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function render(): View
    {
        return view('livewire.pens.form')
            ->title($this->isEditing() ? 'Edit Pen' : 'Add Pen');
    }
}
