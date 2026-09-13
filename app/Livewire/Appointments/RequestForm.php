<?php

declare(strict_types=1);

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Asking to visit the farm.
 *
 * THE ONLY PLACE IN THIS SYSTEM AN ANONYMOUS STRANGER CAN WRITE. Every other
 * write sits behind auth and a Policy, so the two protections below exist
 * nowhere else and will look like clutter to anyone tidying this file later.
 * They are not.
 */
final class RequestForm extends Component
{
    /** Five an hour from one address is far more than a real visitor needs. */
    private const MAX_PER_HOUR = 5;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|string|max:40')]
    public string $contact_number = '';

    /*
     * Optional, and that is a decision rather than leniency. MAIL_* is unset
     * on Render, so the farm cannot write to an address even when it has one.
     * Requiring it would collect it under the implication that it will be used.
     */
    #[Validate('nullable|email|max:190')]
    public ?string $email = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $preferred_date = '';

    #[Validate('required|in:morning,afternoon')]
    public string $preferred_time = 'morning';

    #[Validate('required|integer|min:1|max:50')]
    public int $party_size = 1;

    #[Validate('nullable|string|max:1000')]
    public ?string $message = '';

    /** Set when the visitor asked from a particular bird's page. */
    #[Validate('nullable|exists:broodcocks,id')]
    public ?string $broodcock_id = null;

    /*
     * THE HONEYPOT, and the reason it is spelled `website` rather than
     * something obviously decoy-shaped: a bot fills fields it recognises.
     *
     * A CAPTCHA is not available here. docker/nginx.conf documents a
     * deliberately partial CSP - script-src is unset because Livewire ships
     * Alpine and Alpine needs unsafe-eval - and adding a third-party script
     * widget to a page otherwise free of them is the wrong trade for a farm's
     * contact form. This costs no script at all.
     */
    public string $website = '';

    public bool $submitted = false;

    public function mount(?string $broodcock_id = null): void
    {
        $this->broodcock_id = $broodcock_id;
    }

    public function submit(): void
    {
        /*
         * The limiter runs INSIDE the action, not as `throttle` middleware.
         *
         * Middleware would have to sit on Livewire's shared update endpoint -
         * and the catalogue is on this same page, so it would throttle
         * somebody filtering birds as if they were submitting forms.
         */
        $key = 'appointment:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            $this->addError('name', "That is a lot of requests at once. Please try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.');

            return;
        }

        $validated = $this->validate();

        RateLimiter::hit($key, 3600);

        /*
         * A CAUGHT BOT IS THANKED, NOT CORRECTED.
         *
         * Telling it that it failed is how it learns to fill the form properly
         * next time, so this is indistinguishable from a real success and
         * writes nothing. Placed after the limiter hit on purpose: a bot
         * hammering the form still exhausts its allowance.
         */
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        unset($validated['website']);

        Appointment::create($validated);

        $this->submitted = true;

        $this->reset(['name', 'contact_number', 'email', 'preferred_date', 'message', 'party_size']);
    }

    public function render(): View
    {
        return view('livewire.appointments.request-form');
    }
}
