<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Broodcock;
use App\Models\Pen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pen occupancy and capacity.
 *
 * These assertions used to live inside PenTest, driven through the pen index
 * screen. That screen is gone - pens are data now, not a managed feature, see
 * the note in routes/web.php - but the RULES are not, and they were the part of
 * those tests worth keeping. Deleting a feature is not a reason to stop
 * asserting the domain logic underneath it.
 *
 * The distinction that matters here is "no stated limit" versus "no room left".
 * A pen with capacity 0 means the farm has not said how many birds fit, not
 * that zero fit - and reporting the second when the first is true would tell a
 * keeper a pen is full when it is empty.
 */
final class PenCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_occupancy_counts_the_birds_housed_in_the_pen(): void
    {
        $pen = Pen::factory()->create(['capacity' => 10]);
        Broodcock::factory()->count(3)->create(['pen_id' => $pen->id]);
        Broodcock::factory()->count(2)->create();

        $this->assertSame(3, $pen->occupancy());
    }

    public function test_remaining_capacity_is_what_is_left(): void
    {
        $pen = Pen::factory()->create(['capacity' => 10]);
        Broodcock::factory()->count(4)->create(['pen_id' => $pen->id]);

        $this->assertSame(6, $pen->remainingCapacity());
    }

    /**
     * Capacity 0 is "no limit stated", NOT "no room".
     *
     * Returning 0 here would read on screen as a full pen, which is the
     * opposite of the truth for a pen nobody has sized yet.
     */
    public function test_a_pen_with_no_stated_limit_has_no_remaining_figure(): void
    {
        $pen = Pen::factory()->create(['capacity' => 0]);
        Broodcock::factory()->count(5)->create(['pen_id' => $pen->id]);

        $this->assertNull($pen->remainingCapacity());
        $this->assertFalse($pen->isFull(), 'A pen with no stated limit can never be full.');
    }

    public function test_a_pen_is_full_once_it_reaches_its_capacity(): void
    {
        $pen = Pen::factory()->create(['capacity' => 2]);
        Broodcock::factory()->count(2)->create(['pen_id' => $pen->id]);

        $this->assertTrue($pen->isFull());
    }

    /**
     * Over-capacity is REPORTED, never prevented.
     *
     * Farms overfill pens. Refusing the write would leave staff unable to
     * record what is physically true in the yard, so remaining capacity floors
     * at zero rather than going negative and the pen simply reads as full.
     */
    public function test_an_overfilled_pen_reports_zero_remaining_rather_than_a_negative(): void
    {
        $pen = Pen::factory()->create(['capacity' => 2]);
        Broodcock::factory()->count(5)->create(['pen_id' => $pen->id]);

        $this->assertSame(0, $pen->remainingCapacity());
        $this->assertTrue($pen->isFull());
    }

    /**
     * occupancy() prefers an eager-loaded count when one is present.
     *
     * Without that, any list of pens fires a COUNT per row - and every query in
     * this application is a round trip to Supabase in Tokyo.
     */
    public function test_occupancy_uses_an_eager_loaded_count_when_available(): void
    {
        $pen = Pen::factory()->create(['capacity' => 10]);
        Broodcock::factory()->count(3)->create(['pen_id' => $pen->id]);

        $loaded = Pen::query()->withCount('broodcocks')->findOrFail($pen->id);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->assertSame(3, $loaded->occupancy());
        $this->assertSame(0, $queries, 'occupancy() queried the database despite an eager-loaded count.');
    }
}
