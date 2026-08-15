<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Broodcock;
use App\Models\PerformanceRecord;
use App\Support\PerformanceSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The win rate is the number a buyer decides on, so its edge cases are tested
 * directly rather than inferred from the screen that displays it.
 */
final class PerformanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bird_with_no_records_has_no_win_rate_rather_than_zero_percent(): void
    {
        $bird = Broodcock::factory()->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(0, $summary->totalEvents);
        $this->assertSame(0, $summary->totalContests);
        $this->assertNull($summary->winRate, 'A bird that has never contested has an unknown win rate, not a 0% one.');
        $this->assertNull($summary->averageRating);
        $this->assertSame('No contests yet', $summary->winRateLabel());
        $this->assertSame('Not rated yet', $summary->averageRatingLabel());
    }

    public function test_the_win_rate_is_computed_over_contests_only(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(3)->win()->for($bird)->create();
        PerformanceRecord::factory()->count(1)->loss()->for($bird)->create();

        // Six non-contest events. If these leaked into the denominator the win
        // rate would fall from 75% to 30% - the exact bug this guards.
        PerformanceRecord::factory()->count(4)->conditioning()->for($bird)->create();
        PerformanceRecord::factory()->count(2)->weighIn()->for($bird)->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(10, $summary->totalEvents);
        $this->assertSame(4, $summary->totalContests);
        $this->assertSame(3, $summary->wins);
        $this->assertSame(1, $summary->losses);
        $this->assertSame(0, $summary->draws);
        $this->assertSame(75.0, $summary->winRate);
        $this->assertSame('75.0%', $summary->winRateLabel());
    }

    public function test_draws_count_as_contests_but_not_as_wins(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->draw()->for($bird)->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(2, $summary->totalContests);
        $this->assertSame(1, $summary->draws);
        $this->assertSame(50.0, $summary->winRate);
        $this->assertSame('1-0-1', $summary->recordLabel());
    }

    public function test_a_bird_that_only_ever_trained_has_no_win_rate(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(5)->conditioning()->for($bird)->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(5, $summary->totalEvents);
        $this->assertSame(0, $summary->totalContests);
        $this->assertNull($summary->winRate);
    }

    public function test_the_average_rating_ignores_unrated_events(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->rating(5)->for($bird)->create();
        PerformanceRecord::factory()->rating(3)->for($bird)->create();
        PerformanceRecord::factory()->unrated()->for($bird)->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        // (5 + 3) / 2, not (5 + 3 + 0) / 3.
        $this->assertSame(4.0, $summary->averageRating);
        $this->assertSame('4.0 / 5', $summary->averageRatingLabel());
    }

    public function test_a_bird_with_no_ratings_has_no_average_rating(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(3)->unrated()->for($bird)->create();

        $this->assertNull(PerformanceSummary::forBroodcock($bird)->averageRating);
    }

    public function test_a_soft_deleted_record_is_excluded_from_the_summary(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->for($bird)->create();
        $removed = PerformanceRecord::factory()->loss()->for($bird)->create();

        $removed->delete();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(1, $summary->totalContests);
        $this->assertSame(0, $summary->losses);
        $this->assertSame(100.0, $summary->winRate);
    }

    public function test_another_birds_records_never_leak_into_the_summary(): void
    {
        $bird = Broodcock::factory()->create();
        $other = Broodcock::factory()->create();

        PerformanceRecord::factory()->win()->for($bird)->create();
        PerformanceRecord::factory()->count(9)->loss()->for($other)->create();

        $summary = PerformanceSummary::forBroodcock($bird);

        $this->assertSame(1, $summary->totalContests);
        $this->assertSame(100.0, $summary->winRate);
    }

    public function test_the_summary_can_be_built_from_an_already_loaded_collection(): void
    {
        $bird = Broodcock::factory()->create();

        PerformanceRecord::factory()->count(2)->win()->rating(4)->for($bird)->create();
        PerformanceRecord::factory()->loss()->unrated()->for($bird)->create();
        PerformanceRecord::factory()->conditioning()->for($bird)->create();

        $fromQuery = PerformanceSummary::forBroodcock($bird);
        $fromCollection = PerformanceSummary::fromRecords($bird->performanceRecords()->get());

        // Both paths must agree, or the screen and the report would disagree.
        $this->assertEquals($fromQuery, $fromCollection);
        $this->assertSame(3, $fromCollection->totalContests);
        $this->assertEqualsWithDelta(66.7, $fromCollection->winRate, 0.05);
    }

    public function test_the_empty_summary_is_safe_to_render(): void
    {
        $summary = PerformanceSummary::empty();

        $this->assertSame('No contests yet', $summary->winRateLabel());
        $this->assertSame('Not rated yet', $summary->averageRatingLabel());
        $this->assertSame('0-0-0', $summary->recordLabel());
    }
}
