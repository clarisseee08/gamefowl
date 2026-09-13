<?php

declare(strict_types=1);

namespace App\Livewire\Broodcocks;

use App\Actions\Photos\StorePhoto;
use App\Enums\BroodcockClass;
use App\Enums\BroodcockStatus;
use App\Enums\Sex;
use App\Http\Requests\StoreBroodcockPhotoRequest;
use App\Http\Requests\StoreBroodcockRequest;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class Form extends Component
{
    use WithFileUploads;

    /**
     * Photos chosen while filling in the form.
     *
     * A photo row needs a broodcock_id, so on CREATE the bird genuinely cannot
     * exist yet when the file is picked. Rather than making the keeper save,
     * navigate to the bird, open a tab and upload again, the files are held
     * here and attached immediately after the insert - inside the same
     * transaction-adjacent flow, using the SAME StorePhoto action the dedicated
     * uploader uses, so there is one code path for storing a photo.
     *
     * @var array<int, TemporaryUploadedFile>
     */
    public array $photos = [];

    /** Whether the bird is offered to customers. Defaults to not for sale. */
    public bool $for_sale = false;

    public ?Broodcock $broodcock = null;

    // Form state. Kept as individual public properties rather than an array so
    // wire:model binding and validation error keys line up with the rule names.
    public ?string $band_number = null;

    public string $name = '';

    public ?string $bloodline = null;

    public string $class = '';

    public string $sex = '';

    public ?string $date_hatched = null;

    public ?string $date_acquired = null;

    public ?string $weight = null;

    public ?string $color = null;

    public ?string $comb_type = null;

    public ?string $leg_color = null;

    public ?string $distinguishing_marks = null;

    public string $status = '';

    public ?string $sire_id = null;

    public ?string $dam_id = null;

    public ?string $notes = null;

    public function mount(?Broodcock $broodcock = null): void
    {
        if ($broodcock?->exists) {
            $this->authorize('update', $broodcock);
            $this->broodcock = $broodcock;

            $this->fill([
                'for_sale' => (bool) $broodcock->for_sale,
                'band_number' => $broodcock->band_number,
                'name' => $broodcock->name,
                'bloodline' => $broodcock->bloodline,
                'class' => $broodcock->class->value,
                'sex' => $broodcock->sex->value,
                'date_hatched' => $broodcock->date_hatched?->toDateString(),
                'date_acquired' => $broodcock->date_acquired?->toDateString(),
                'weight' => $broodcock->weight,
                'color' => $broodcock->color,
                'comb_type' => $broodcock->comb_type,
                'leg_color' => $broodcock->leg_color,
                'distinguishing_marks' => $broodcock->distinguishing_marks,
                'status' => $broodcock->status->value,
                'sire_id' => $broodcock->sire_id ? (string) $broodcock->sire_id : null,
                'dam_id' => $broodcock->dam_id ? (string) $broodcock->dam_id : null,
                'notes' => $broodcock->notes,
            ]);

            return;
        }

        $this->authorize('create', Broodcock::class);
        $this->class = BroodcockClass::Ordinary->value;
        $this->sex = Sex::Male->value;
        $this->status = BroodcockStatus::Active->value;
    }

    public function isEditing(): bool
    {
        return $this->broodcock?->exists ?? false;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        // The photo rules live here rather than in StoreBroodcockRequest: that
        // request describes the BIRD, and a file picked in the browser is not
        // part of the record's shape.
        //
        // They are DELEGATED, not restated. These were literals - max:10 and
        // max:4096 - which meant this form ignored GFMS_PHOTO_MAX_PER_BIRD and
        // GFMS_PHOTO_MAX_KB entirely, and, lacking the `mimes` rule the
        // uploader applies, accepted SVG and GIF that the uploader on the very
        // next screen rejects. One upload path, one set of rules.
        return StoreBroodcockRequest::rulesFor($this->broodcock) + [
            'for_sale' => ['boolean'],
            'photos' => StoreBroodcockPhotoRequest::optionalPhotoBagRules(),
            'photos.*' => StoreBroodcockPhotoRequest::singlePhotoRules(),
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return StoreBroodcockRequest::attributeNames();
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        // Photo messages come from the same place as the photo rules, so the
        // limit quoted to the keeper is always the limit actually enforced.
        return StoreBroodcockRequest::messageOverrides()
            + StoreBroodcockPhotoRequest::messagesFor();
    }

    /** Validate a single field as soon as the user leaves it. */
    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function save(): void
    {
        $data = $this->validate();

        // `photos` is validated with the rest of the form but is NOT a column
        // on the bird, so it has to come out before the write - passing it
        // through hits a MassAssignmentException on create.
        unset($data['photos']);

        // Normalise empty strings from <select> and <input> to real nulls, so
        // "no sire selected" is stored as NULL rather than 0 or ''.
        foreach (['band_number', 'bloodline', 'date_hatched', 'date_acquired', 'weight',
            'color', 'comb_type', 'leg_color', 'distinguishing_marks', 'sire_id', 'dam_id',
            'notes'] as $nullable) {
            if (($data[$nullable] ?? null) === '') {
                $data[$nullable] = null;
            }
        }

        // A single-table write, but wrapped anyway: the activity-log entry is
        // written by an Eloquent event in the same request, and the two should
        // succeed or fail together.
        $saved = DB::transaction(function () use ($data): Broodcock {
            if ($this->isEditing()) {
                $this->broodcock->update($data);

                return $this->broodcock;
            }

            return Broodcock::create($data);
        });

        $this->attachPhotos($saved);

        session()->flash('success', $this->isEditing()
            ? "Changes to {$saved->name} have been saved."
            : "{$saved->name} has been added to the farm records.");

        $this->redirectRoute('broodcocks.show', $saved, navigate: true);
    }

    /**
     * Candidate sires: male birds that are still breeding-eligible, plus the
     * currently-selected sire even if it has since been retired - otherwise
     * editing an old record would silently drop its pedigree.
     *
     * @return Collection<int, Broodcock>
     */
    #[Computed]
    public function sireOptions(): Collection
    {
        return $this->parentOptions(Sex::Male, $this->sire_id);
    }

    /** @return Collection<int, Broodcock> */
    #[Computed]
    public function damOptions(): Collection
    {
        return $this->parentOptions(Sex::Female, $this->dam_id);
    }

    /** @return Collection<int, Broodcock> */
    private function parentOptions(Sex $sex, ?string $currentlySelected): Collection
    {
        return Broodcock::query()
            ->where('sex', $sex->value)
            ->when($this->broodcock?->exists, fn ($q) => $q->whereKeyNot($this->broodcock->id))
            ->where(function ($q) use ($currentlySelected): void {
                $q->breedingEligible();

                if ($currentlySelected !== null && $currentlySelected !== '') {
                    $q->orWhere('id', $currentlySelected);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'band_number', 'bloodline']);
    }

    /** @return array<int, BroodcockClass> */
    public function classOptions(): array
    {
        return BroodcockClass::cases();
    }

    /** @return array<int, Sex> */
    public function sexOptions(): array
    {
        return Sex::cases();
    }

    /** @return array<int, BroodcockStatus> */
    public function statusOptions(): array
    {
        return BroodcockStatus::cases();
    }

    public function render(): View
    {
        return view('livewire.broodcocks.form');
    }

    /**
     * Attaches any photos picked on the form to the saved bird.
     *
     * Deliberately NOT inside the transaction above: the files go to object
     * storage, which cannot be rolled back. Committing the bird first means a
     * storage failure costs a photo rather than the whole record.
     */
    private function attachPhotos(Broodcock $bird): void
    {
        if ($this->photos === []) {
            return;
        }

        if (! auth()->user()?->can('create', BroodcockPhoto::class)) {
            return;
        }

        $storePhoto = app(StorePhoto::class);

        foreach ($this->photos as $photo) {
            $storePhoto->handle($bird, $photo, auth()->user());
        }

        $this->photos = [];
    }
}
