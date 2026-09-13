<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\Report;
use App\Reports\ReportDefinition;
use App\Reports\ReportRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The reports hub.
 *
 * Picking a report and setting a date range happens here; the actual export is
 * a plain GET to ReportController, which is what writes the audit row. Keeping
 * generation on a normal HTTP request rather than a Livewire action is
 * deliberate - a file download cannot be delivered through a Livewire update.
 */
final class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Report::class);
    }

    public function updating(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    /** Default the range to the last 12 months so a first click is useful. */
    public function useLastYear(): void
    {
        $this->from = now()->subYear()->toDateString();
        $this->to = now()->toDateString();
    }

    public function clearRange(): void
    {
        $this->reset(['from', 'to']);
    }

    /** @return Collection<int, ReportDefinition> */
    #[Computed]
    public function reports(): Collection
    {
        return app(ReportRegistry::class)->all();
    }

    /**
     * The audit trail. Every generation is recorded, which is what makes the
     * "digital audit trail" claim demonstrable live during the defense.
     *
     * @return LengthAwarePaginator<int, Report>
     */
    #[Computed]
    public function history(): LengthAwarePaginator
    {
        return Report::query()
            ->with('generatedBy:id,full_name')
            ->orderByDesc('generated_at')
            ->paginate(10);
    }

    /** Query string appended to each export link. */
    public function exportQuery(): string
    {
        return http_build_query(array_filter([
            'from' => $this->from,
            'to' => $this->to,
        ]));
    }

    public function render(): View
    {
        return view('livewire.reports.index')
            ->title('Reports');
    }
}
