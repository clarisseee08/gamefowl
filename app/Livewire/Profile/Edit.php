<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Actions\Photos\StorePhoto;
use App\Http\Requests\StoreBroodcockPhotoRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * A user's own profile.
 *
 * Deliberately separate from Users\Form, which is the OWNER managing other
 * people. The two look similar and are not: this one can never change a role or
 * an active flag, and it always operates on auth()->user() rather than on a
 * route-bound model, so there is no id a user could tamper with to edit somebody
 * else's account.
 */
final class Edit extends Component
{
    use WithFileUploads;

    public string $full_name = '';

    public string $email = '';

    public string $contact_number = '';

    public string $address = '';

    public string $position = '';

    public ?TemporaryUploadedFile $photo = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $status = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->full_name = $user->full_name ?? '';
        $this->email = $user->email ?? '';
        $this->contact_number = $user->contact_number ?? '';
        $this->address = $user->address ?? '';
        $this->position = $user->position ?? '';
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'contact_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:120'],
            // Same limits and the same accepted types as every other upload in
            // the system, read from config rather than restated. A phone photo
            // is routinely 8 MB, so the limit has to be shown in the UI as well
            // as enforced here.
            'photo' => ['nullable', ...StoreBroodcockPhotoRequest::optionalSinglePhotoRules()],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return StoreBroodcockPhotoRequest::messagesFor('photo') + [
            'email.unique' => 'Another account already uses that email address.',
        ];
    }

    public function updatedPhoto(): void
    {
        // Validate on selection rather than on save, so an oversized file is
        // rejected while the user is still looking at the picker.
        $this->validateOnly('photo');
    }

    public function save(): void
    {
        $user = auth()->user();

        // UserPolicy::updateOwnProfile() existed and was called from nowhere.
        // This component cannot actually be tricked into editing somebody else
        // - it reads auth()->user() and takes no id - but an ability that is
        // never invoked is an ability nobody maintains, and the day this screen
        // grows a parameter the check needs to already be here.
        $this->authorize('updateOwnProfile', $user);

        $data = $this->validate();

        if ($this->photo !== null) {
            // gfms.photo_disk, NOT filesystems.default.
            //
            // These are not the same disk in production and the difference is
            // silent data loss. Render sets GFMS_PHOTO_DISK=supabase and never
            // sets FILESYSTEM_DISK, so the default resolves to 'local' -
            // storage/app/private inside a container whose filesystem is wiped
            // on every restart, which on the free instance type is constant.
            // Bird photos already read this key; profile photos did not, so
            // they were the only uploads that did not survive a restart.
            $disk = StorePhoto::disk();

            $path = $this->photo->store('profile-photos', $disk);

            // Remove the previous file only after the new one is safely stored;
            // the reverse order loses the old photo if the upload fails.
            $this->deleteStoredPhoto($user->profile_photo_disk, $user->profile_photo_path);

            $user->profile_photo_path = $path;
            $user->profile_photo_disk = $disk;
        }

        $user->fill([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'contact_number' => $data['contact_number'] ?: null,
            'address' => $data['address'] ?: null,
            'position' => $data['position'] ?: null,
        ]);

        $user->save();

        $this->photo = null;
        $this->status = 'Your profile has been updated.';
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        $this->authorize('updateOwnProfile', $user);

        $this->deleteStoredPhoto($user->profile_photo_disk, $user->profile_photo_path);

        $user->profile_photo_path = null;
        $user->profile_photo_disk = null;
        $user->save();

        $this->status = 'Your photo has been removed.';
    }

    public function updatePassword(): void
    {
        $this->authorize('updateOwnProfile', auth()->user());

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'current_password.current_password' => 'That is not your current password.',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->status = 'Your password has been changed.';
    }

    private function deleteStoredPhoto(?string $disk, ?string $path): void
    {
        if (blank($path)) {
            return;
        }

        // The disk is read from the ROW, so a photo uploaded before the disk
        // changed still resolves to the place it was actually written. The
        // fallback only covers rows that predate profile_photo_disk existing.
        Storage::disk($disk ?: StorePhoto::disk())->delete($path);
    }

    public function render(): View
    {
        return view('livewire.profile.edit', ['user' => auth()->user()])
            ->title('Your Profile');
    }
}
