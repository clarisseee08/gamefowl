<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BroodcockPhoto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The single source of truth for photo upload rules.
 *
 * The upload screen is a Livewire component rather than a plain form POST, so
 * it cannot resolve a Form Request out of the container. Per conventions §5 the
 * rules are therefore exposed as static methods that both this request and
 * App\Livewire\Photos\Upload call, instead of being written out twice and
 * drifting apart.
 */
final class StoreBroodcockPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BroodcockPhoto::class) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'photos' => self::photoBagRules(),
            'photos.*' => self::singlePhotoRules(),
            'caption' => self::captionRules(),
        ];
    }

    /**
     * Rules for the whole set of files chosen in one go.
     *
     * @return array<int, string>
     */
    public static function photoBagRules(): array
    {
        return ['required', 'array', 'min:1', 'max:'.self::maxPerBroodcock()];
    }

    /**
     * Rules for one uploaded file.
     *
     * `image` verifies the file really decodes as an image, `mimes` pins the
     * accepted types by their detected MIME (not the filename the browser sent),
     * and `max` is enforced here as well as in php.ini because the client can
     * be told anything and believed for nothing.
     *
     * @return array<int, string>
     */
    public static function singlePhotoRules(): array
    {
        return [
            'required',
            'image',
            'mimes:'.implode(',', self::acceptedMimes()),
            'max:'.self::maxKilobytes(),
        ];
    }

    /** @return array<int, string> */
    public static function captionRules(): array
    {
        return ['nullable', 'string', 'max:255'];
    }

    /**
     * Plain-language messages. Farm staff need to be told what to do about the
     * problem, not which validation rule name failed.
     *
     * @return array<string, string>
     */
    public static function messagesFor(string $field = 'photos.*'): array
    {
        $maxMb = round(self::maxKilobytes() / 1024, 1);
        $types = strtoupper(implode(', ', self::acceptedMimes()));

        return [
            $field.'.required' => 'Please choose at least one photo.',
            $field.'.image' => 'That file is not a photo. Please choose a picture taken with your camera.',
            $field.'.mimes' => "Only {$types} photos can be uploaded. Please choose a different file.",
            $field.'.max' => "That photo is too large. Please choose one smaller than {$maxMb} MB.",
            'photos.required' => 'Please choose at least one photo.',
            'photos.max' => 'You chose too many photos at once. Please upload up to '.self::maxPerBroodcock().' at a time.',
            'caption.max' => 'Please keep the description under 255 characters.',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return self::messagesFor();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'photos' => 'photos',
            'photos.*' => 'photo',
            'caption' => 'description',
        ];
    }

    // -----------------------------------------------------------------
    // Configured limits - read through config() so farm policy can change
    // without touching application code.
    // -----------------------------------------------------------------

    public static function maxKilobytes(): int
    {
        return (int) config('gfms.photos.max_kilobytes');
    }

    public static function maxPerBroodcock(): int
    {
        return (int) config('gfms.photos.max_per_broodcock');
    }

    /** @return array<int, string> */
    public static function acceptedMimes(): array
    {
        /** @var array<int, string> $mimes */
        $mimes = config('gfms.photos.accepted_mimes', []);

        return $mimes;
    }

    /** The `accept` attribute for the file input - a courtesy, never the check. */
    public static function acceptAttribute(): string
    {
        return collect(self::acceptedMimes())
            ->map(fn (string $ext): string => 'image/'.($ext === 'jpg' ? 'jpeg' : $ext))
            ->unique()
            ->implode(',');
    }
}
