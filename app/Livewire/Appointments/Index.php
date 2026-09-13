<?php

declare(strict_types=1);

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The visit requests, and what the farm decided about them.
 *
 * CONFIRMING TELLS THE VISITOR NOTHING. There is no email - MAIL_* is unset on
 * Render, so Laravel would accept one, report it sent and write it to stderr.
 * The status is the farm's own note of what it agreed on the telephone, not a
 * message to anybody, and the screen says to ring them rather than implying
 * the system already has.
 */
final class Index extends Component
{
    use WithPagination;

    /*
     * Opens on what needs a decision rather than on everything ever asked. A
     * queue that opens full of handled requests is a queue nobody works
     * through.
     */
    #[Url(except: AppointmentStatus::Pending->value)]
    public string $status = AppointmentStatus::Pending->value;

    public function mount(): void
    {
        $this->authorize('viewAny', Appointment::class);
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function confirm(int $appointment): void
    {
        $this->decide($appointment, AppointmentStatus::Confirmed);
    }

    public function decline(int $appointment): void
    {
        $this->decide($appointment, AppointmentStatus::Declined);
    }

    public function markVisited(int $appointment): void
    {
        $this->decide($appointment, AppointmentStatus::Completed);
    }

    /**
     * Set the status, and record who decided and when.
     *
     * Assigned rather than mass-updated on purpose: status, handled_by and
     * handled_at are outside the model's $fillable so that nothing a visitor
     * submits can ever reach them. This is the only path that writes them.
     */
    private function decide(int $appointment, AppointmentStatus $status): void
    {
        $record = Appointment::query()->findOrFail($appointment);

        $this->authorize('update', $record);

        $record->status = $status;
        $record->handled_by = auth()->id();
        $record->handled_at = now();
        $record->save();

        session()->flash('success', "The request from {$record->name} is now {$status->label()}.");
    }

    /** @return LengthAwarePaginator<int, Appointment> */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return Appointment::query()
            // The bird is shown per row, so it is eager-loaded or the table is
            // one extra query per request against a database in Tokyo.
            ->with('broodcock:id,name,band_number,bloodline')
            ->where('status', $this->status)
            // Soonest first: the request for next Tuesday matters more than the
            // one for next month, whatever order they arrived in.
            ->orderBy('preferred_date')
            ->paginate(config('gfms.per_page'));
    }

    /** @return array<int, AppointmentStatus> */
    public function statusOptions(): array
    {
        return AppointmentStatus::cases();
    }

    public function render(): View
    {
        return view('livewire.appointments.index')
            ->layout('layouts::app')
            ->title('Visit requests');
    }
}
