<?php

declare(strict_types=1);

namespace App\Livewire\Health;

use App\Actions\Health\CreateHealthRecord;
use App\Actions\Health\UpdateHealthRecord;
use App\Enums\HealthRecordType;
use App\Http\Requests\StoreHealthRecordRequest;
use App\Http\Requests\UpdateHealthRecordRequest;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create or edit a health record.
 *
 * One component serves both, because the two forms are the same eight fields
 * under the same rules; splitting them would only guarantee they drift apart.
 *
 * Property names are snake_case to match the database columns, so the rule
 * keys published by the Form Requests apply here without any translation.
 */
final class Form extends Component
{
    /** Locked: which record is being edited is never re-assignable from the browser. */
    #[Locked]
    public ?int $recordId = null;

    public string $broodcock_id = '';

    public string $record_type = '';

    public ?string $product_name = null;

    public ?string $dosage = null;

    public string $checkup_date = '';

    public ?string $next_due_date = null;

    public ?string $condition = null;

    public ?string $remarks = null;

    /** @param HealthRecord|null $record Route-bound from /health/{record}/edit; null when creating. */
    public function mount(?HealthRecord $record = null): void
    {
        if ($record?->exists) {
            $this->authorize('update', $record);

            $this->recordId = $record->id;
            $this->broodcock_id = (string) $record->broodcock_id;
            $this->record_type = $record->record_type->value;
            $this->product_name = $record->product_name;
            $this->dosage = $record->dosage;
            $this->checkup_date = $record->checkup_date->toDateString();
            $this->next_due_date = $record->next_due_date?->toDateString();
            $this->condition = $record->condition;
            $this->remarks = $record->remarks;

            return;
        }

        $this->authorize('create', HealthRecord::class);

        // Most records are written on the day of the check-up, so today is the
        // useful default - it removes the most common interaction entirely.
        $this->checkup_date = today()->toDateString();

        // /health/create?broodcock=12 pre-selects the bird, so "Add health
        // record" from a bird's profile does not make staff hunt for it again.
        $preselected = (int) request()->integer('broodcock');

        if ($preselected > 0) {
            $this->broodcock_id = (string) $preselected;
        }
    }

    public function isEditing(): bool
    {
        return $this->recordId !== null;
    }

    public function save(): void
    {
        $record = $this->record;

        // Authorized again here, not only in mount(): a crafted request can
        // reach this method without mount() ever having run.
        if ($record instanceof HealthRecord) {
            $this->authorize('update', $record);

            $data = $this->validate(
                UpdateHealthRecordRequest::rulesFor(),
                UpdateHealthRecordRequest::messagesFor(),
                UpdateHealthRecordRequest::attributesFor(),
            );

            app(UpdateHealthRecord::class)->handle($record, $data);

            session()->flash('success', 'The health record was saved.');
        } else {
            $this->authorize('create', HealthRecord::class);

            $data = $this->validate(
                StoreHealthRecordRequest::rulesFor(),
                StoreHealthRecordRequest::messagesFor(),
                StoreHealthRecordRequest::attributesFor(),
            );

            /** @var User $actor */
            $actor = auth()->user();

            app(CreateHealthRecord::class)->handle($data, $actor);

            session()->flash('success', 'The health record was added.');
        }

        $this->redirectRoute('health.index', navigate: true);
    }

    /** The record being edited, or null when creating a new one. */
    #[Computed]
    public function record(): ?HealthRecord
    {
        return $this->recordId === null
            ? null
            : HealthRecord::query()->find($this->recordId);
    }

    /**
     * Birds on the picker. Sold and deceased birds are left out of a NEW
     * record - you do not treat a bird that is not on the farm - but the bird
     * already attached to an existing record is always kept in the list, so
     * editing an old record can never silently reassign it.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function birds(): Collection
    {
        return Broodcock::query()
            ->select(['id', 'name', 'band_number'])
            ->where(function (Builder $query): void {
                $query->onFarm();

                if ($this->broodcock_id !== '') {
                    $query->orWhere('id', (int) $this->broodcock_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, HealthRecordType> */
    #[Computed]
    public function recordTypes(): array
    {
        return HealthRecordType::cases();
    }

    /**
     * Vaccinations and dewormings recur, so the form nudges for a follow-up
     * date. A nudge, not a rule - a one-off treatment legitimately has none.
     */
    #[Computed]
    public function expectsNextDueDate(): bool
    {
        return HealthRecordType::tryFrom($this->record_type)?->expectsNextDueDate() ?? false;
    }

    /** Label of the currently chosen record type, for the follow-up nudge. */
    #[Computed]
    public function selectedTypeLabel(): ?string
    {
        return HealthRecordType::tryFrom($this->record_type)?->label();
    }

    public function render(): View
    {
        return view('livewire.health.form')
            ->title($this->isEditing() ? 'Edit Health Record' : 'Add Health Record');
    }
}
