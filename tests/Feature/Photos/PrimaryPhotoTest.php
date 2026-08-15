<?php

declare(strict_types=1);

namespace Tests\Feature\Photos;

use App\Actions\Photos\SetPrimaryPhoto;
use App\Http\Controllers\BroodcockPhotoController;
use App\Livewire\Photos\Gallery;
use App\Models\Broodcock;
use App\Models\BroodcockPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The invariant under test: a bird has at most one primary photo, always.
 */
final class PrimaryPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake((string) config('gfms.photo_disk'));

        // The gallery view links every <img> at the photo route, which lives in
        // routes/web.php - a file this module does not own. Registered here
        // only when missing, so these tests use the real route once it lands.
        if (! Route::has('photos.show')) {
            Route::middleware(['web', 'auth', 'active'])
                ->get('/photos/{photo}', [BroodcockPhotoController::class, 'show'])
                ->name('photos.show');

            Route::getRoutes()->refreshNameLookups();
        }
    }

    public function test_two_photos_of_the_same_bird_can_never_both_be_primary(): void
    {
        $bird = Broodcock::factory()->create();
        $first = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $second = BroodcockPhoto::factory()->forBroodcock($bird)->create();
        $third = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        $action = app(SetPrimaryPhoto::class);

        // Promote each one in turn. After every promotion exactly one row of
        // this bird carries the flag - not two, and never zero.
        foreach ([$second, $third, $first, $second] as $promoted) {
            $action->handle($promoted);

            $this->assertSame(
                1,
                $bird->photos()->where('is_primary', true)->count(),
                'A bird must have exactly one primary photo after every promotion.',
            );

            $this->assertTrue($promoted->fresh()->is_primary);
        }

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertFalse($third->fresh()->is_primary);
    }

    public function test_promoting_a_photo_leaves_other_birds_alone(): void
    {
        $birdA = Broodcock::factory()->create();
        $birdB = Broodcock::factory()->create();

        $primaryOfA = BroodcockPhoto::factory()->forBroodcock($birdA)->primary()->create();
        $primaryOfB = BroodcockPhoto::factory()->forBroodcock($birdB)->primary()->create();
        $otherOfB = BroodcockPhoto::factory()->forBroodcock($birdB)->create();

        app(SetPrimaryPhoto::class)->handle($otherOfB);

        $this->assertTrue($primaryOfA->fresh()->is_primary, 'Bird A keeps its own main photo.');
        $this->assertFalse($primaryOfB->fresh()->is_primary);
        $this->assertTrue($otherOfB->fresh()->is_primary);
    }

    public function test_promoting_the_photo_that_is_already_primary_is_harmless(): void
    {
        $bird = Broodcock::factory()->create();
        $photo = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();

        app(SetPrimaryPhoto::class)->handle($photo);

        $this->assertTrue($photo->fresh()->is_primary);
        $this->assertSame(1, $bird->photos()->where('is_primary', true)->count());
    }

    public function test_the_demotion_and_promotion_happen_in_one_transaction(): void
    {
        $bird = Broodcock::factory()->create();
        $old = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $new = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        // DB::listen never sees BEGIN/COMMIT, so atomicity is observed from
        // inside instead: every write this action makes must happen while a
        // transaction is open. The baseline is the level RefreshDatabase
        // already holds, so the action must push it higher.
        $baseline = DB::transactionLevel();
        $levelsSeen = [];

        BroodcockPhoto::updated(function () use (&$levelsSeen): void {
            $levelsSeen[] = DB::transactionLevel();
        });

        app(SetPrimaryPhoto::class)->handle($new);

        $this->assertCount(2, $levelsSeen, 'Both the demotion and the promotion must be written.');

        foreach ($levelsSeen as $level) {
            $this->assertGreaterThan(
                $baseline,
                $level,
                'Every write in SetPrimaryPhoto must happen inside its own transaction.',
            );
        }

        $this->assertFalse($old->fresh()->is_primary);
        $this->assertTrue($new->fresh()->is_primary);
    }

    public function test_the_primary_photo_relation_returns_the_promoted_photo(): void
    {
        $bird = Broodcock::factory()->create();
        BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $new = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        app(SetPrimaryPhoto::class)->handle($new);

        $this->assertSame($new->id, $bird->fresh()->primaryPhoto->id);
    }

    public function test_staff_can_promote_a_photo_from_the_gallery(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        $old = BroodcockPhoto::factory()->forBroodcock($bird)->primary()->create();
        $new = BroodcockPhoto::factory()->forBroodcock($bird)->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->call('setPrimary', $new->id)
            ->assertHasNoErrors();

        $this->assertTrue($new->fresh()->is_primary);
        $this->assertFalse($old->fresh()->is_primary);
        $this->assertSame(1, $bird->photos()->where('is_primary', true)->count());
    }

    public function test_a_photo_belonging_to_another_bird_cannot_be_promoted_through_this_gallery(): void
    {
        $staff = User::factory()->staff()->create();
        $birdA = Broodcock::factory()->create();
        $birdB = Broodcock::factory()->create();

        BroodcockPhoto::factory()->forBroodcock($birdA)->primary()->create();
        $foreign = BroodcockPhoto::factory()->forBroodcock($birdB)->create();

        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $birdA])
            ->call('setPrimary', $foreign->id)
            ->assertStatus(404);

        $this->assertFalse($foreign->fresh()->is_primary);
    }

    public function test_the_gallery_loads_its_relations_without_an_n_plus_one(): void
    {
        $staff = User::factory()->staff()->create();
        $bird = Broodcock::factory()->create();
        BroodcockPhoto::factory()->count(6)->forBroodcock($bird)->create();

        // Model::shouldBeStrict() is on outside production, so a lazy load in
        // the gallery view would throw rather than quietly firing six queries.
        Livewire::actingAs($staff)
            ->test(Gallery::class, ['broodcock' => $bird])
            ->assertOk();
    }
}
