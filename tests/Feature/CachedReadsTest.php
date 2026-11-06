<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Livewire\Broodcocks\Show;
use App\Livewire\Dashboard\Overview;
use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\Concerns\InvalidatesRecordCache;
use App\Models\HealthRecord;
use App\Models\MortalityRecord;
use App\Models\Pen;
use App\Models\PerformanceRecord;
use App\Models\User;
use App\Support\CachedUserProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reads that are cached because the database is a ~240ms round trip away -
 * and the rule that makes caching them safe: any write shows at once.
 */
final class CachedReadsTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Every farm record clears the cache when written
    // -----------------------------------------------------------------

    public function test_every_farm_record_model_clears_the_cache_when_written(): void
    {
        foreach ([Broodcock::class, BroodcockPhoto::class, HealthRecord::class, BreedingRecord::class, MortalityRecord::class, PerformanceRecord::class, Pen::class] as $model) {
            $this->assertContains(
                InvalidatesRecordCache::class,
                class_uses_recursive($model),
                $model.' is read by cached screens, so writing it must clear the cache.'
            );
        }
    }

    public function test_a_new_overdue_vaccination_shows_on_the_dashboard_at_once(): void
    {
        $owner = User::factory()->owner()->create();
        $bird = Broodcock::factory()->create(['status' => BroodcockStatus::Active]);

        $this->assertSame(0, Livewire::actingAs($owner)->test(Overview::class)->instance()->overdueCount);

        HealthRecord::factory()->for($bird)->overdue()->create();

        $this->assertSame(1, Livewire::actingAs($owner)->test(Overview::class)->instance()->overdueCount);
    }

    public function test_a_deleted_death_record_leaves_the_dashboard_count_at_once(): void
    {
        $owner = User::factory()->owner()->create();
        $death = MortalityRecord::factory()->diedOn(now()->toDateString())->create();

        $this->assertSame(1, Livewire::actingAs($owner)->test(Overview::class)->instance()->deathsThisYear);

        $death->delete();

        $this->assertSame(0, Livewire::actingAs($owner)->test(Overview::class)->instance()->deathsThisYear);
    }

    public function test_a_renamed_bird_is_shown_renamed_on_its_page_at_once(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create(['name' => 'Before']);

        Livewire::actingAs($staff)->test(Show::class, ['broodcock' => $bird])->assertSee('Before');

        $bird->update(['name' => 'After']);

        Livewire::actingAs($staff)->test(Show::class, ['broodcock' => $bird->fresh()])
            ->assertSee('After')
            ->assertDontSee('Before');
    }

    /** The dashboard's figures come from the cache once they have been read. */
    public function test_the_dashboard_reads_the_database_once_and_then_the_cache(): void
    {
        $owner = User::factory()->owner()->create();
        Broodcock::factory()->count(3)->create();

        Livewire::actingAs($owner)->test(Overview::class);

        DB::enableQueryLog();
        Livewire::actingAs($owner)->test(Overview::class);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $queries, 'A second dashboard view should not touch the database.');
    }

    // -----------------------------------------------------------------
    // Photos: the cached lookup still answers the visibility question
    // -----------------------------------------------------------------

    public function test_a_visitor_loses_a_photo_the_moment_its_bird_is_recorded_dead(): void
    {
        Storage::fake((string) config('gfms.photo_disk'));
        $bird = Broodcock::factory()->create(['status' => BroodcockStatus::Active, 'is_external' => false]);
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->create();
        Storage::disk($photo->disk)->put($photo->path, 'photo-bytes');

        $this->get(route('photos.show', $photo))->assertOk();

        $bird->update(['status' => BroodcockStatus::Deceased]);

        $this->get(route('photos.show', $photo))->assertForbidden();
    }

    public function test_photos_may_be_kept_by_the_viewers_own_browser_for_a_month(): void
    {
        Storage::fake((string) config('gfms.photo_disk'));
        $photo = BroodcockPhoto::factory()->create();
        Storage::disk($photo->disk)->put($photo->path, 'photo-bytes');

        $cacheControl = (string) $this->actingAs(User::factory()->staff()->create())
            ->get(route('photos.show', $photo))
            ->assertOk()
            ->headers->get('Cache-Control');

        // private: authorized per user, so a shared cache must never keep it.
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=2592000', $cacheControl);
    }

    // -----------------------------------------------------------------
    // The signed-in user
    // -----------------------------------------------------------------

    public function test_the_signed_in_user_is_looked_up_once_and_then_read_from_the_cache(): void
    {
        $user = User::factory()->staff()->create();
        $provider = Auth::createUserProvider('users');

        $this->assertInstanceOf(CachedUserProvider::class, $provider);

        $provider->retrieveById($user->id);

        DB::enableQueryLog();
        $again = $provider->retrieveById($user->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($user->id, $again?->getAuthIdentifier());
        $this->assertSame(0, $queries);
    }

    /** The reason the cached copy is dropped on every save. */
    public function test_a_deactivation_applies_on_the_users_very_next_request(): void
    {
        $user = User::factory()->staff()->create(['is_active' => true]);
        $provider = Auth::createUserProvider('users');
        $provider->retrieveById($user->id);

        $user->update(['is_active' => false]);

        $this->assertFalse($provider->retrieveById($user->id)?->is_active);
    }

    public function test_a_deactivated_user_is_signed_out_on_their_next_page(): void
    {
        $user = User::factory()->staff()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['is_active' => false]);
        Auth::forgetGuards();

        $this->withSession(['login_web_'.sha1(SessionGuard::class) => $user->id])
            ->get(route('dashboard'))
            ->assertRedirect();
    }

    public function test_an_unknown_id_is_nobody(): void
    {
        $this->assertNull(Auth::createUserProvider('users')->retrieveById(999_999));
    }
}
