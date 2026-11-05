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
    /** Rendered inside the list's form dialog rather than as a page of its own. */
    public bool $inDialog = false;

    #[Locked]
    public ?int $recordId = null;

    /**
     * Where saving lands. Set by the page that opened the form in a dialog
     * (a bird's page sends you back to that bird); otherwise the list.
     * Locked, so the browser cannot point it anywhere else.
     */
    #[Locked]
    public ?string $returnUrl = null;

    public string $broodcock_id = '';

    public string $record_type = '';

    public ?string $product_name = null;

    public ?string $dosage = null;

    /** "Others" in a preset dropdown: the text box beside it holds the value. */
    public const OTHER = '__other__';

    /**
     * What the Product Name and Condition dropdowns show: a preset, OTHER, or
     * nothing. The value saved is still product_name / condition - a preset
     * copies itself there, and OTHER leaves the text box to fill it.
     */
    public string $product_choice = '';

    public string $condition_choice = '';

    /** Dosage as an amount and a unit (ml, mg, g, or OTHER for "1 tablet"); saved as "0.5 ml". */
    public string $dosage_amount = '';

    public string $dosage_unit = 'ml';

    public string $checkup_date = '';

    public ?string $next_due_date = null;

    public ?string $condition = null;

    public ?string $remarks = null;

    /**
     * @param  HealthRecord|null  $record  Route-bound from /health/{record}/edit; null when creating.
     * @param  int|null  $forBroodcock  The bird to pre-select, when opened from that bird's page.
     */
    public function mount(?HealthRecord $record = null, ?int $forBroodcock = null): void
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

            $this->deriveChoices();

            return;
        }

        $this->authorize('create', HealthRecord::class);

        // Most records are written on the day of the check-up, so today is the
        // useful default - it removes the most common interaction entirely.
        $this->checkup_date = today()->toDateString();

        // /health/create?broodcock=12 pre-selects the bird, so "Add health
        // record" from a bird's profile does not make staff hunt for it again.
        $preselected = $forBroodcock ?? (int) request()->integer('broodcock');

        if ($preselected > 0) {
            $this->broodcock_id = (string) $preselected;
        }
    }

    /**
     * An existing record's text, shown back in the dropdowns: a value on the
     * list selects it, anything else opens under "Others" with its text
     * intact, and a dosage like "0.5 ml" splits into amount and unit.
     */
    private function deriveChoices(): void
    {
        $this->product_choice = $this->choiceFor($this->product_name, $this->productOptions);
        $this->condition_choice = $this->choiceFor($this->condition, $this->conditionOptions);

        $dosage = trim((string) $this->dosage);

        if ($dosage === '') {
            return;
        }

        if (preg_match('/^([0-9]*\.?[0-9]+)\s*(ml|mg|g)$/i', $dosage, $match) === 1) {
            $this->dosage_amount = $match[1];
            $this->dosage_unit = strtolower($match[2]);
        } else {
            $this->dosage_amount = $dosage;
            $this->dosage_unit = self::OTHER;
        }
    }

    /** @param list<string> $options */
    private function choiceFor(?string $value, array $options): string
    {
        $value = trim((string) $value);

        return match (true) {
            $value === '' => '',
            in_array($value, $options, true) => $value,
            default => self::OTHER,
        };
    }

    public function updatedProductChoice(string $choice): void
    {
        $this->product_name = $this->valueForChoice($choice, $this->product_name, $this->productOptions);
    }

    public function updatedConditionChoice(string $choice): void
    {
        $this->condition = $this->valueForChoice($choice, $this->condition, $this->conditionOptions);
    }

    /**
     * A preset becomes the value. "Others" empties the box for typing - unless
     * it already holds custom text, which switching back must not throw away.
     *
     * @param  list<string>  $options
     */
    private function valueForChoice(string $choice, ?string $current, array $options): ?string
    {
        if ($choice === self::OTHER) {
            return in_array((string) $current, $options, true) ? null : $current;
        }

        return $choice === '' ? null : $choice;
    }

    /**
     * "0.5" and "ml" saved as "0.5 ml"; nothing when no amount is given. The
     * product and condition dropdowns bind without a round trip, so their
     * choice reaches the columns here too, just before validation.
     */
    private function composeDosage(): void
    {
        $this->product_name = $this->valueForChoice($this->product_choice, $this->product_name, $this->productOptions);
        $this->condition = $this->valueForChoice($this->condition_choice, $this->condition, $this->conditionOptions);

        $amount = trim($this->dosage_amount);
        $unit = $this->dosage_unit === self::OTHER ? '' : $this->dosage_unit;

        $this->dosage = $amount === '' ? null : trim($amount.' '.$unit);
    }

    /**
     * The amount must be a number when a unit is chosen; under "Others" it is
     * free text ("1 tablet").
     *
     * @return array<string, list<string>>
     */
    private function dosageRules(): array
    {
        return ['dosage_amount' => $this->dosage_unit === self::OTHER
            ? ['nullable', 'string', 'max:120']
            : ['nullable', 'numeric', 'gt:0', 'max:100000']];
    }

    /** @return list<string> */
    #[Computed]
    public function productOptions(): array
    {
        return config('gfms.health_presets.products', []);
    }

    /** @return list<string> */
    #[Computed]
    public function conditionOptions(): array
    {
        return config('gfms.health_presets.conditions', []);
    }

    /** @return list<string> */
    #[Computed]
    public function dosageUnits(): array
    {
        return config('gfms.health_presets.dosage_units', ['ml', 'mg', 'g']);
    }

    /** The chosen bird with its photograph, shown under the picker. */
    #[Computed]
    public function selectedBird(): ?Broodcock
    {
        return $this->broodcock_id === ''
            ? null
            : Broodcock::query()->with('primaryPhoto')->find((int) $this->broodcock_id);
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

            $this->composeDosage();

            $data = $this->validate(
                [...UpdateHealthRecordRequest::rulesFor(), ...$this->dosageRules()],
                UpdateHealthRecordRequest::messagesFor(),
                [...UpdateHealthRecordRequest::attributesFor(), 'dosage_amount' => 'dosage'],
            );
            unset($data['dosage_amount']);

            app(UpdateHealthRecord::class)->handle($record, $data);

            session()->flash('success', 'The health record was saved.');
        } else {
            $this->authorize('create', HealthRecord::class);

            $this->composeDosage();

            $data = $this->validate(
                [...StoreHealthRecordRequest::rulesFor(), ...$this->dosageRules()],
                StoreHealthRecordRequest::messagesFor(),
                [...StoreHealthRecordRequest::attributesFor(), 'dosage_amount' => 'dosage'],
            );
            unset($data['dosage_amount']);

            /** @var User $actor */
            $actor = auth()->user();

            app(CreateHealthRecord::class)->handle($data, $actor);

            session()->flash('success', 'The health record was added.');
        }

        $this->redirect($this->returnUrl ?? route('health.index'), navigate: true);
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
