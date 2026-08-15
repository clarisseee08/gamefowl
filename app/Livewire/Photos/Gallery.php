<?php

declare(strict_types=1);

namespace App\Livewire\Photos;

use App\Actions\Photos\DeletePhoto;
use App\Actions\Photos\SetPrimaryPhoto;
use App\Http\Requests\StoreBroodcockPhotoRequest;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

final class Gallery extends Component
{
    public Broodcock $broodcock;

    /** Which photo's caption is being edited inline, if any. */
    public ?int $editingCaptionFor = null;

    public string $captionDraft = '';

    /**
     * Shown inside this component. A session flash would not appear until the
     * next full page load, because a Livewire update re-renders only this
     * component and not the layout that displays flashed messages.
     */
    public string $status = '';

    public function mount(Broodcock $broodcock): void
    {
        $this->authorize('viewAny', BroodcockPhoto::class);

        $this->broodcock = $broodcock;
    }

    /**
     * @return Collection<int, BroodcockPhoto>
     */
    #[Computed]
    public function photos(): Collection
    {
        return $this->broodcock
            ->photos()
            // Model::shouldBeStrict() is on, so anything the view touches must
            // be loaded here. The view shows who uploaded each photo, and the
            // Policy is asked about each photo's bird.
            ->with(['uploadedBy:id,full_name', 'broodcock:id,name,band_number'])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
    }

    #[On('photos-updated')]
    public function refreshPhotos(): void
    {
        unset($this->photos);
    }

    public function setPrimary(int $photoId, SetPrimaryPhoto $setPrimaryPhoto): void
    {
        $photo = $this->findPhoto($photoId);

        $this->authorize('update', $photo);

        $setPrimaryPhoto->handle($photo);

        unset($this->photos);

        $this->status = 'Main photo updated.';
    }

    public function delete(int $photoId, DeletePhoto $deletePhoto): void
    {
        $photo = $this->findPhoto($photoId);

        $this->authorize('delete', $photo);

        $deletePhoto->handle($photo);

        unset($this->photos);

        // Frees a slot on the upload component sharing this page.
        $this->dispatch('photos-updated');

        $this->status = 'Photo deleted.';
    }

    public function startEditingCaption(int $photoId): void
    {
        $photo = $this->findPhoto($photoId);

        $this->authorize('update', $photo);

        $this->editingCaptionFor = $photoId;
        $this->captionDraft = (string) $photo->caption;
        $this->status = '';
        $this->resetErrorBag('captionDraft');
    }

    public function cancelEditingCaption(): void
    {
        $this->editingCaptionFor = null;
        $this->captionDraft = '';
        $this->resetErrorBag('captionDraft');
    }

    public function saveCaption(): void
    {
        if ($this->editingCaptionFor === null) {
            return;
        }

        $photo = $this->findPhoto($this->editingCaptionFor);

        $this->authorize('update', $photo);

        $this->validate(
            ['captionDraft' => StoreBroodcockPhotoRequest::captionRules()],
            ['captionDraft.max' => 'Please keep the description under 255 characters.'],
        );

        $caption = trim($this->captionDraft);

        $photo->update(['caption' => $caption === '' ? null : $caption]);

        $this->cancelEditingCaption();

        unset($this->photos);

        $this->status = 'Description saved.';
    }

    /**
     * Always resolved from this bird's own photos.
     *
     * The id arrives from the browser, so it is treated as a request for
     * "photo N", not as proof that photo N belongs on this page - otherwise a
     * changed id would act on another bird's photo through this bird's screen.
     */
    private function findPhoto(int $photoId): BroodcockPhoto
    {
        /** @var BroodcockPhoto|null $photo */
        $photo = $this->photos->firstWhere('id', $photoId);

        abort_if($photo === null, 404, 'That photo is no longer on this bird.');

        return $photo;
    }

    public function render(): View
    {
        return view('livewire.photos.gallery');
    }
}
