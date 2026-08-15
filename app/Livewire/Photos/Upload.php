<?php

declare(strict_types=1);

namespace App\Livewire\Photos;

use App\Actions\Photos\StorePhoto;
use App\Http\Requests\StoreBroodcockPhotoRequest;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class Upload extends Component
{
    use WithFileUploads;

    public Broodcock $broodcock;

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public string $caption = '';

    /**
     * Shown inside this component rather than flashed to the session.
     *
     * A Livewire update re-renders only this component, so a session flash
     * would not surface in the layout until the next full page load - by which
     * time it is confusing rather than reassuring.
     */
    public string $status = '';

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('create', BroodcockPhoto::class);

        $this->broodcock = $broodcock;
    }

    /**
     * Validate the moment a file lands, not at save time.
     *
     * On a slow phone connection, telling someone their photo was too big only
     * after they have waited for four more to upload is the difference between
     * a warning and a wasted trip to the pen.
     */
    public function updatedPhotos(): void
    {
        $this->status = '';

        $this->validateOnly('photos.*', $this->rules(), StoreBroodcockPhotoRequest::messagesFor());

        if (count($this->photos) > $this->remainingSlots) {
            $this->addError('photos', $this->tooManyMessage());
        }
    }

    /** Deleting a photo elsewhere on the page frees up a slot here. */
    #[On('photos-updated')]
    public function refreshCounts(): void
    {
        $this->clearCounts();
    }

    /** Drop one file from the pending list before anything is saved. */
    public function removePending(int $index): void
    {
        unset($this->photos[$index]);

        $this->photos = array_values($this->photos);
        $this->resetErrorBag('photos');
    }

    public function save(StorePhoto $storePhoto): void
    {
        // The gate, not the hidden button. Checked again here because mount()
        // ran on a different request and roles can change in between.
        $this->authorize('create', BroodcockPhoto::class);

        $this->validate($this->rules(), StoreBroodcockPhotoRequest::messagesFor());

        if (count($this->photos) > $this->remainingSlots) {
            throw ValidationException::withMessages(['photos' => $this->tooManyMessage()]);
        }

        $caption = trim($this->caption);

        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        foreach ($this->photos as $photo) {
            $storePhoto->handle($this->broodcock, $photo, $actor, [
                'caption' => $caption === '' ? null : $caption,
            ]);
        }

        $saved = count($this->photos);

        $this->reset(['photos', 'caption']);
        $this->clearCounts();

        $this->dispatch('photos-updated');

        $this->status = $saved === 1
            ? 'Photo saved.'
            : "{$saved} photos saved.";
    }

    /**
     * All three counts are cached per request and all three are derived from
     * the same query, so forgetting one of them leaves the screen telling
     * someone they have room for a photo they have just used up.
     */
    private function clearCounts(): void
    {
        unset($this->photoCount, $this->remainingSlots, $this->isFull);
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'photos' => StoreBroodcockPhotoRequest::photoBagRules(),
            'photos.*' => StoreBroodcockPhotoRequest::singlePhotoRules(),
            'caption' => StoreBroodcockPhotoRequest::captionRules(),
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['photos' => 'photos', 'photos.*' => 'photo', 'caption' => 'description'];
    }

    #[Computed]
    public function photoCount(): int
    {
        return $this->broodcock->photos()->count();
    }

    #[Computed]
    public function remainingSlots(): int
    {
        return max(0, StoreBroodcockPhotoRequest::maxPerBroodcock() - $this->photoCount);
    }

    #[Computed]
    public function isFull(): bool
    {
        return $this->remainingSlots === 0;
    }

    private function tooManyMessage(): string
    {
        $max = StoreBroodcockPhotoRequest::maxPerBroodcock();
        $left = $this->remainingSlots;

        if ($left === 0) {
            return "This bird already has the maximum of {$max} photos. Please delete one before adding another.";
        }

        return $left === 1
            ? 'There is room for 1 more photo on this bird. Please remove some of the ones you chose.'
            : "There is room for {$left} more photos on this bird. Please remove some of the ones you chose.";
    }

    public function render(): View
    {
        return view('livewire.photos.upload');
    }
}
