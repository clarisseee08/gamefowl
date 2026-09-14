<?php

declare(strict_types=1);

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
     * OPENS ON EVERYTHING, and this is a reversal worth recording.
     *
     * It used to open on Pending, on the argument that a queue full of handled
     * requests is a queue nobody works through. That argument is sound for a
     * farm with a steady stream of requests and wrong for this one: with a
     * handful of visits a year, the pending queue is empty most of the time, so
     * the screen opened reading "No visit requests to show" while the farm was
     * holding a confirmed visit for next Tuesday. A screen that hides the only
     * record it has is worse than one that shows a handled request.
     *
     * The Showing filter still narrows to Awaiting reply in one click.
     */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'preferred_date')]
    public string $sortBy = 'preferred_date';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    /** Columns a user is allowed to sort by - never interpolate raw input into SQL. */
    private const SORTABLE = ['preferred_date', 'name', 'party_size', 'status'];

    /**
     * One press sets the column and the direction together.
     *
     * Pressing the column already sorted flips it; pressing another takes it
     * ascending, because "show me this column" almost always means "from the
     * top" on first press.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

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

    /** Id of the request the owner is being asked to confirm deletion of. */
    public ?int $confirmingDeleteId = null;

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** The request awaiting confirmation, so the dialog can name it. */
    #[Computed]
    public function requestPendingDeletion(): ?Appointment
    {
        if ($this->confirmingDeleteId === null) {
            return null;
        }

        return Appointment::query()->find($this->confirmingDeleteId);
    }

    /**
     * Delete a visit request outright.
     *
     * THIS IS PERMANENT, unlike every other delete in this application.
     * Appointment has no SoftDeletes trait and the table has no deleted_at, so
     * there is nothing to restore from - the Policy says as much by returning
     * false from restore() and forceDelete(). The dialog has to tell the truth
     * about that rather than borrowing the health record's "can be restored by
     * the owner", which would be a lie here.
     *
     * Owner only, and that is the Policy's decision rather than this screen's:
     * staff can decide a request, only an owner can erase one.
     */
    public function delete(): void
    {
        $record = $this->requestPendingDeletion;

        if ($record === null) {
            $this->confirmingDeleteId = null;

            return;
        }

        // The Policy is the gate. Hiding the button was only a courtesy.
        $this->authorize('delete', $record);

        $name = $record->name;

        $record->delete();

        $this->confirmingDeleteId = null;
        unset($this->requests, $this->requestPendingDeletion);

        session()->flash('success', "The request from {$name} was deleted.");
    }

    /** @return LengthAwarePaginator<int, Appointment> */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return Appointment::query()
            // The bird is shown per row, so it is eager-loaded or the table is
            // one extra query per request against a database in Tokyo.
            ->with('broodcock:id,name,band_number,bloodline')
            // An empty status is "all requests", which is now the default - see
            // the note on the property for why that reversed.
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            // Soonest first by default: the request for next Tuesday matters
            // more than the one for next month, whatever order they arrived in.
            // The keeper can now sort by any of the allow-listed columns, and
            // the list is checked a second time here because the URL can set
            // $sortBy directly without ever passing through sort().
            ->orderBy(
                in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'preferred_date',
                $this->sortDirection === 'asc' ? 'asc' : 'desc'
            )
            ->orderByDesc('id')
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
