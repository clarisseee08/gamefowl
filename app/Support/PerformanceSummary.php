<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PerformanceResult;
use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use Illuminate\Support\Collection;

/**
 * Per-bird performance statistics.
 *
 * Every figure here is DERIVED, never stored. A stored win count contradicts
 * its own inputs the moment a record is edited or soft-deleted.
 *
 * The win rate is computed over CONTEST events only - see scopeContests() on
 * PerformanceRecord. Counting conditioning sessions and weigh-ins in the
 * denominator would silently dilute a bird's record, which is the exact
 * statistic a buyer decides on.
 */
final readonly class PerformanceSummary
{
    public function __construct(
        public int $totalEvents,
        public int $totalContests,
        public int $wins,
        public int $losses,
        public int $draws,
        /** Percentage 0-100, or null when the bird has never contested. */
        public ?float $winRate,
        /** Mean of the 1-5 ratings actually given, or null when none were. */
        public ?float $averageRating,
    ) {}

    /**
     * Build the summary with a single aggregate query per bird.
     *
     * Deliberately NOT built by looping the timeline in PHP: the timeline is
     * paginated, so a PHP tally would silently report only the visible page.
     */
    public static function forBroodcock(Broodcock $broodcock): self
    {
        /** @var object{total_events: int|string|null, wins: int|string|null, losses: int|string|null, draws: int|string|null, rated: int|string|null, rating_sum: int|string|null}|null $row */
        $row = PerformanceRecord::query()
            ->where('broodcock_id', $broodcock->id)
            ->selectRaw('count(*) as total_events')
            ->selectRaw('sum(case when result = ? then 1 else 0 end) as wins', [PerformanceResult::Win->value])
            ->selectRaw('sum(case when result = ? then 1 else 0 end) as losses', [PerformanceResult::Loss->value])
            ->selectRaw('sum(case when result = ? then 1 else 0 end) as draws', [PerformanceResult::Draw->value])
            ->selectRaw('count(rating) as rated')
            ->selectRaw('sum(rating) as rating_sum')
            ->first();

        if ($row === null) {
            return self::empty();
        }

        return self::fromTallies(
            totalEvents: (int) ($row->total_events ?? 0),
            wins: (int) ($row->wins ?? 0),
            losses: (int) ($row->losses ?? 0),
            draws: (int) ($row->draws ?? 0),
            ratedCount: (int) ($row->rated ?? 0),
            ratingSum: (int) ($row->rating_sum ?? 0),
        );
    }

    /**
     * Build from an already-loaded collection - used when the caller has the
     * full record set in memory and a second query would be wasteful.
     *
     * @param  Collection<int, PerformanceRecord>  $records
     */
    public static function fromRecords(Collection $records): self
    {
        $rated = $records->filter(fn (PerformanceRecord $r): bool => $r->rating !== null);

        return self::fromTallies(
            totalEvents: $records->count(),
            wins: $records->where('result', PerformanceResult::Win)->count(),
            losses: $records->where('result', PerformanceResult::Loss)->count(),
            draws: $records->where('result', PerformanceResult::Draw)->count(),
            ratedCount: $rated->count(),
            ratingSum: (int) $rated->sum('rating'),
        );
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, 0, 0, null, null);
    }

    /** Win rate as a display string - "no contests yet" is not "0%". */
    public function winRateLabel(): string
    {
        if ($this->winRate === null) {
            return 'No contests yet';
        }

        return number_format($this->winRate, 1).'%';
    }

    public function averageRatingLabel(): string
    {
        if ($this->averageRating === null) {
            return 'Not rated yet';
        }

        return number_format($this->averageRating, 1).' / 5';
    }

    /** Win-loss-draw in the shorthand the farm actually uses. */
    public function recordLabel(): string
    {
        return "{$this->wins}-{$this->losses}-{$this->draws}";
    }

    private static function fromTallies(
        int $totalEvents,
        int $wins,
        int $losses,
        int $draws,
        int $ratedCount,
        int $ratingSum,
    ): self {
        $contests = $wins + $losses + $draws;

        return new self(
            totalEvents: $totalEvents,
            totalContests: $contests,
            wins: $wins,
            losses: $losses,
            draws: $draws,
            // Guard the divide-by-zero explicitly. A bird with no contests has
            // an UNKNOWN win rate, not a 0% one, and the difference matters to
            // anyone reading the page.
            winRate: $contests === 0 ? null : round($wins / $contests * 100, 1),
            averageRating: $ratedCount === 0 ? null : round($ratingSum / $ratedCount, 2),
        );
    }
}
