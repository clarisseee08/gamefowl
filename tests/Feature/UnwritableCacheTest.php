<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroodcockStatus;
use App\Models\Broodcock;
use App\Models\HealthRecord;
use App\Models\User;
use App\Support\RecordCache;
use ErrorException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * A cache that cannot be written must never fail a page.
 *
 * The production incident this pins: the container's boot script ran artisan
 * as root after handing storage/ to the web server, leaving root-owned cache
 * folders, and every signed-in user got a 500 on the dashboard because one of
 * its cached figures hashed into such a folder. The store below fails exactly
 * as that one did - file_put_contents() refused - on every write.
 */
final class UnwritableCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::extend('unwritable', fn () => Cache::repository(new class extends ArrayStore
        {
            public function put($key, $value, $seconds)
            {
                throw new ErrorException('file_put_contents(/var/www/html/storage/framework/cache/data/ab/cd/ef): Failed to open stream: Permission denied');
            }

            public function forever($key, $value)
            {
                return $this->put($key, $value, 0);
            }
        }));

        config(['cache.stores.unwritable' => ['driver' => 'unwritable'], 'cache.default' => 'unwritable']);
        Cache::forgetDriver('unwritable');
    }

    public function test_the_dashboard_opens_for_a_signed_in_owner(): void
    {
        $owner = User::factory()->owner()->create();
        Broodcock::factory()->count(2)->create();

        // Signed in through the session, so the user is resolved by the
        // cached user provider exactly as on a real request - not handed to
        // the guard by actingAs().
        $this->withSession(['login_web_'.sha1(SessionGuard::class) => $owner->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_cached_reads_fall_back_to_the_database(): void
    {
        $this->assertSame(42, RecordCache::remember('anything', fn (): int => 42));
    }

    /** It runs after the row is written, so throwing would report a saved record as failed. */
    public function test_saving_a_record_still_succeeds(): void
    {
        $bird = Broodcock::factory()->create(['status' => BroodcockStatus::Active]);

        $record = HealthRecord::factory()->for($bird)->create();

        $this->assertModelExists($record);
    }

    public function test_the_signed_in_user_is_read_from_the_database_instead(): void
    {
        $user = User::factory()->staff()->create();

        $this->assertSame($user->id, Auth::createUserProvider('users')->retrieveById($user->id)?->getAuthIdentifier());
    }
}
