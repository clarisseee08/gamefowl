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
     * ┌─────────────────────────────────────────────────────────────────┐
     * │ WORK IN PROGRESS - the five report classes below are NOT written │
     * │ yet. The interface, this registry, ReportController and the PDF  │
     * │ layout are complete and are the contract they must satisfy.      │
     * │                                                                  │
     * │ Nothing calls this registry yet (there are no /reports routes),  │
     * │ so the application and the test suite are unaffected. Calling    │
     * │ all() or make() before the classes exist WILL fatal.             │
     * │                                                                  │
     * │ Remaining to build: the 5 report classes + their PDF templates,  │
     * │ the reports index screen, routes, dashboard, user management,    │
     * │ customer portal and the database seeder.                         │
     * └─────────────────────────────────────────────────────────────────┘
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
