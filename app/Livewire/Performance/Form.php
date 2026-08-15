<?php

declare(strict_types=1);

namespace App\Livewire\Performance;

use App\Actions\Performance\CreatePerformanceRecord;
use App\Actions\Performance\UpdatePerformanceRecord;
use App\Enums\PerformanceEventType;
use App\Enums\PerformanceResult;
use App\Http\Requests\StorePerformanceRecordRequest;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Form extends Component
{
    public ?PerformanceRecord $record = null;

    // Form state as individual public properties rather than an array, so
    // wire:model bindings and validation error keys line up with the rule
    // names on StorePerformanceRecordRequest.
    public ?string $broodcock_id = null;

    public ?string $event_date = null;

    public string $event_type = '';

    public ?string $weight = null;

    public string $result = '';

    public ?string $duration_seconds = null;

    public ?string $rating = null;

    public ?string $remarks = null;

    public function mount(?PerformanceRecord $record = null, ?Broodcock $broodcock = null): void
    {
        if ($record?->exists) {
            $this->authorize('update', $record);
            $this->record = $record;

            $this->fill([
                'broodcock_id' => (string) $record->broodcock_id,
                'event_date' => $record->event_date->toDateString(),
                'event_type' => $record->event_type->value,
                'weight' => $record->weight,
                'result' => $record->result->value,
                'duration_seconds' => $record->duration_seconds !== null ? (string) $record->duration_seconds : null,
                'rating' => $record->rating !== null ? (string) $record->rating : null,
                'remarks' => $record->remarks,
            ]);

            return;
        }

        $this->authorize('create', PerformanceRecord::class);

        // Arriving from a bird's page pre-selects that bird - the commonest
        // path, and one less thing for staff to get wrong.
        //
        // Accepted either as a route segment (/performance/create/{broodcock})
        // or as a query string (?broodcock=12), so the link from the timeline
        // works whichever shape the route table takes.
        $preselected = $broodcock?->exists === true
            ? $broodcock->id
            : request()->integer('broodcock');

        if ($preselected > 0 && Broodcock::query()->whereKey($preselected)->exists()) {
            $this->broodcock_id = (string) $preselected;
        }

        $this->event_date = now()->toDateString();
        $this->event_type = PerformanceEventType::Sparring->value;

        // Left blank on purpose for a contest event: the result is required,
        // so the user must state it rather than have "not applicable"
        // pre-selected and a real bout silently recorded as no contest.
        $this->result = '';
    }

    public function isEditing(): bool
    {
        return $this->record?->exists ?? false;
    }

    /**
     * The same rules the HTTP Form Request enforces - imported, not retyped.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return StorePerformanceRecordRequest::rulesFor();
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return StorePerformanceRecordRequest::attributeNames();
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return StorePerformanceRecordRequest::messageOverrides();
    }

    /**
     * Whether a win/loss/draw is meaningful for the currently-chosen event
     * type. Drives whether the result selector is shown at all.
     */
    public function resultApplies(): bool
    {
        return PerformanceEventType::tryFrom($this->event_type)?->hasContestResult() ?? false;
    }

    /** Plain label for the chosen event type, used in the explanatory notice. */
    public function eventTypeLabel(): string
    {
        return PerformanceEventType::tryFrom($this->event_type)?->label() ?? 'event of this kind';
    }

    /**
     * Changing the event type to a weigh-in or conditioning session clears any
     * win/loss already chosen. Without this, picking "Derby / Win" and then
     * switching to "Weigh-in" would leave a weigh-in recorded as a win.
     */
    public function updatedEventType(): void
    {
        if (! $this->resultApplies()) {
            $this->result = PerformanceResult::NotApplicable->value;
            $this->resetErrorBag('result');

            return;
        }

        if ($this->result === PerformanceResult::NotApplicable->value) {
            $this->result = '';
        }
    }

    /** Validate a single field as soon as the user leaves it. */
    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function setRating(int $stars): void
    {
        // Tapping the same star again clears the rating - otherwise a rating
        // given by accident can never be taken back without reloading.
        $this->rating = $this->rating === (string) $stars ? null : (string) $stars;
    }

    public function save(CreatePerformanceRecord $create, UpdatePerformanceRecord $update): void
    {
        // Belt and braces: normalise BEFORE validating, so a tampered payload
        // that sets "weigh-in + win" is rejected as a weigh-in with no result
        // rather than stored as a win.
        if (! $this->resultApplies()) {
            $this->result = PerformanceResult::NotApplicable->value;
        }

        $data = $this->validate();

        // Empty strings from <input> and <select> become real nulls, so
        // "no weight taken" is stored as NULL rather than 0.
        foreach (['weight', 'duration_seconds', 'rating', 'remarks'] as $nullable) {
            if (($data[$nullable] ?? null) === '') {
                $data[$nullable] = null;
            }
        }

        if ($this->isEditing()) {
            $this->authorize('update', $this->record);
            $saved = $update->handle($this->record, $data);
        } else {
            $this->authorize('create', PerformanceRecord::class);

            /** @var User $actor */
            $actor = auth()->user();
            $saved = $create->handle($data, $actor);
        }

        $bird = Broodcock::query()->find($saved->broodcock_id);
        $birdName = $bird?->displayName() ?? 'the bird';

        session()->flash('success', $this->isEditing()
            ? "Changes to the {$saved->event_type->label()} record for {$birdName} have been saved."
            : "The {$saved->event_type->label()} record for {$birdName} has been saved.");

        $this->redirectRoute('performance.index', navigate: true);
    }

    /**
     * Birds that can have a performance recorded against them, plus the bird
     * already on the record even if it has since been sold or has died -
     * otherwise editing an old record would silently reassign it.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function birdOptions(): Collection
    {
        return Broodcock::query()
            ->where(function ($query): void {
                $query->onFarm();

                if ($this->broodcock_id !== null && $this->broodcock_id !== '') {
                    $query->orWhere('id', $this->broodcock_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'band_number']);
    }

    /** @return array<int, PerformanceEventType> */
    public function eventTypeOptions(): array
    {
        return PerformanceEventType::cases();
    }

    /** Only real outcomes are offered; "not applicable" is set by the system. */
    /** @return array<int, PerformanceResult> */
    public function resultOptions(): array
    {
        return array_values(array_filter(
            PerformanceResult::cases(),
            fn (PerformanceResult $result): bool => $result->countsTowardRecord(),
        ));
    }

    public function render(): View
    {
        return view('livewire.performance.form');
    }
}
