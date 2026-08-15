<?php

declare(strict_types=1);

namespace App\Livewire\Health;

use App\Models\HealthRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The vaccination and deworming schedule - thesis objective (c).
 *
 * Everything on this screen is derived from `next_due_date`; nothing about
 * "overdue" or "due soon" is stored, so the screen cannot disagree with the
 * records it is built from.
 *
 * Two groups, in the order a farm actually works them:
 *   1. Overdue      - next_due_date is in the past. Most overdue first, because
 *                     the bird waiting longest is the one at most risk.
 *   2. Due soon     - next_due_date falls inside config('gfms.vaccination_warning_days').
 *                     Soonest first, which is the order they will be done in.
 *
 * The two lists paginate independently, so paging through the overdue list
 * does not move the upcoming list out from under the user.
 */
#[Title('Vaccination Schedule')]
final class Schedule extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewSchedule', HealthRecord::class);
    }

    /** The look-ahead window, in days. Farm policy, so it lives in config. */
    public function warningDays(): int
    {
        return (int) config('gfms.vaccination_warning_days', HealthRecord::UPCOMING_WINDOW_DAYS);
    }

    /**
     * Follow-ups whose due date has already passed.
     *
     * Ascending by due date puts the oldest - i.e. the most overdue - first.
     *
     * @return LengthAwarePaginator<int, HealthRecord>
     */
    #[Computed]
    public function overdue(): LengthAwarePaginator
    {
        return HealthRecord::query()
            ->with('broodcock')
            ->overdue()
            ->orderBy('next_due_date')
            ->orderBy('id')
            ->paginate(config('gfms.per_page'), pageName: 'overduePage');
    }

    /**
     * Follow-ups falling due inside the warning window, soonest first.
     *
     * scopeDueSoon() starts at today, so a record is in exactly one of the two
     * lists - never both, never neither.
     *
     * @return LengthAwarePaginator<int, HealthRecord>
     */
    #[Computed]
    public function dueSoon(): LengthAwarePaginator
    {
        return HealthRecord::query()
            ->with('broodcock')
            ->dueSoon($this->warningDays())
            ->orderBy('next_due_date')
            ->orderBy('id')
            ->paginate(config('gfms.per_page'), pageName: 'upcomingPage');
    }

    /** Counts for the summary tiles - cheap, and they answer "how bad is it?" at a glance. */
    #[Computed]
    public function overdueCount(): int
    {
        return HealthRecord::query()->overdue()->count();
    }

    #[Computed]
    public function dueSoonCount(): int
    {
        return HealthRecord::query()->dueSoon($this->warningDays())->count();
    }

    public function render(): View
    {
        return view('livewire.health.schedule');
    }
}
