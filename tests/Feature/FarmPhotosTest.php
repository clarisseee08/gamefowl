<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\FarmPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The farm's own photographs, dealt out across the public slideshows.
 *
 * THE RULE the owner set: no photograph appears on two pages, and which page
 * gets which is random. A visitor who goes Home -> About -> Visit -> Sign in
 * should never meet the same bird twice, and the next visitor should get a
 * different deal. The deal is seeded from the session, so it holds steady for
 * one visit - a page does not reshuffle on Back, and the front page's
 * Livewire re-renders do not swap the hero photograph mid-look.
 *
 * Near-identical shots of one bird are tagged with a shared `look` and are
 * never dealt into the same slideshow, so two slides in a row never show what
 * reads as the same picture.
 */
final class FarmPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_photograph_is_dealt_exactly_once_across_the_slideshows(): void
    {
        foreach ([1, 2, 3, 99, 123456] as $seed) {
            $deal = FarmPhotos::deal($seed);

            $this->assertSame(array_keys(FarmPhotos::SLOTS), array_keys($deal));

            foreach (FarmPhotos::SLOTS as $slot => $size) {
                $this->assertCount($size, $deal[$slot], "slot {$slot}, seed {$seed}");
            }

            $dealt = array_merge(...array_values(array_map(
                fn (array $photos): array => array_column($photos, 'file'),
                $deal,
            )));

            sort($dealt);
            $all = array_column(FarmPhotos::PHOTOS, 'file');
            sort($all);

            $this->assertSame($all, $dealt, "seed {$seed}");
        }
    }

    public function test_the_slots_hold_exactly_as_many_photographs_as_there_are(): void
    {
        $this->assertSame(count(FarmPhotos::PHOTOS), array_sum(FarmPhotos::SLOTS));
    }

    public function test_lookalike_photographs_never_share_a_slideshow(): void
    {
        foreach (range(1, 200) as $seed) {
            foreach (FarmPhotos::deal($seed) as $slot => $photos) {
                $looks = array_filter(array_column($photos, 'look'));

                $this->assertSame(
                    array_values(array_unique($looks)),
                    array_values($looks),
                    "slot {$slot}, seed {$seed}",
                );
            }
        }
    }

    public function test_the_same_seed_always_gives_the_same_deal(): void
    {
        $this->assertSame(FarmPhotos::deal(42), FarmPhotos::deal(42));
    }

    public function test_different_seeds_give_different_deals(): void
    {
        $deals = array_map(fn (int $seed): string => serialize(FarmPhotos::deal($seed)), range(1, 20));

        $this->assertGreaterThan(15, count(array_unique($deals)));
    }

    public function test_a_visit_uses_the_deal_seeded_in_its_session(): void
    {
        $this->withSession([FarmPhotos::SESSION_KEY => 7]);

        $this->assertSame(
            array_column(FarmPhotos::deal(7)['about'], 'file'),
            array_map(fn (array $slide): string => basename($slide['src']), FarmPhotos::slides('about')),
        );
    }

    public function test_a_new_visit_is_given_a_seed_that_then_stays_put(): void
    {
        $this->withSession([]);

        $first = FarmPhotos::slides('visit');
        $seed = session(FarmPhotos::SESSION_KEY);

        $this->assertIsInt($seed);
        $this->assertSame($first, FarmPhotos::slides('visit'));
        $this->assertSame($seed, session(FarmPhotos::SESSION_KEY));
    }

    public function test_each_slide_carries_its_source_alt_text_and_crop(): void
    {
        $this->withSession([FarmPhotos::SESSION_KEY => 7]);

        foreach (FarmPhotos::slides('sign-in') as $slide) {
            $this->assertStringStartsWith(asset('photos/farm/'), $slide['src']);
            $this->assertNotSame('', $slide['alt']);
            $this->assertMatchesRegularExpression('/^\d{1,3}% \d{1,3}%$/', $slide['position']);
        }
    }

    public function test_an_unknown_slideshow_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FarmPhotos::slides('catalogue');
    }

    public function test_every_listed_photograph_exists_and_every_file_is_listed(): void
    {
        $listed = array_column(FarmPhotos::PHOTOS, 'file');
        sort($listed);

        $onDisk = array_map('basename', glob(public_path('photos/farm/*.webp')));
        sort($onDisk);

        $this->assertSame($onDisk, $listed);
    }

    /**
     * The four pages, rendered in one visit, show four disjoint sets of the
     * farm's photographs - and none of the old stock photographs.
     */
    public function test_the_public_pages_never_repeat_a_photograph_within_one_visit(): void
    {
        $this->withSession([FarmPhotos::SESSION_KEY => 314159]);

        $pages = [
            'home' => route('home'),
            'about' => route('public.about'),
            'visit' => route('public.visit'),
            'sign-in' => route('login'),
        ];

        $seen = [];

        foreach ($pages as $page => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Slide 1 has a plain src; later slides are lazy-loaded through
            // @js(), which escapes the slashes (photos\/farm\/...).
            preg_match_all('#photos(?:\\\\)?/farm(?:\\\\)?/([a-z0-9-]+\.webp)#', $html, $matches);
            $files = array_values(array_unique($matches[1]));

            $this->assertNotEmpty($files, "{$page} shows no farm photographs");
            $this->assertSame([], array_intersect($files, $seen), "{$page} repeats a photograph");
            $this->assertDoesNotMatchRegularExpression(
                '#photos(?:\\\\)?/(hero-farm|rooster-walking|rooster-portrait|rooster-profile|rooster-crowing|hens-fence|visit-field)\.webp#',
                $html,
                "{$page} still shows a stock photograph",
            );

            $seen = [...$seen, ...$files];
        }

        $this->assertCount(count(FarmPhotos::PHOTOS), $seen);
    }
}
