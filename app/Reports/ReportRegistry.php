<?php

declare(strict_types=1);

namespace App\Reports;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The single place that knows which reports exist.
 *
 * Keeping the list here rather than scattered across routes means a new report
 * is registered once and immediately gains the shared CSV export, PDF export
 * and audit-trail behaviour.
 */
final class ReportRegistry
{
    /*
     * Adding a report is one line here plus two files: the class, and its PDF
     * template at the path its pdfView() returns. CSV export, PDF export and
     * the audit row are inherited from ReportController and need no work.
     *
     * ReportExportTest walks these keys and asserts each one produces a real
     * CSV and a real PDF, so a report registered without its template fails the
     * suite rather than a download.
     *
     * @var array<string, class-string<ReportDefinition>>
     */
    private const REPORTS = [
        'broodcock_inventory' => BroodcockInventoryReport::class,
        'health_compliance' => HealthComplianceReport::class,
        'breeding_performance' => BreedingPerformanceReport::class,
        'mortality' => MortalityReport::class,
        'performance_history' => PerformanceHistoryReport::class,
    ];

    /** @return Collection<int, ReportDefinition> */
    public function all(): Collection
    {
        return collect(self::REPORTS)
            ->keys()
            ->map(fn (string $key) => $this->make($key))
            ->values();
    }

    public function make(string $key): ReportDefinition
    {
        if (! isset(self::REPORTS[$key])) {
            throw new InvalidArgumentException("Unknown report [{$key}].");
        }

        return app(self::REPORTS[$key]);
    }

    public function has(string $key): bool
    {
        return isset(self::REPORTS[$key]);
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys(self::REPORTS);
    }
}
