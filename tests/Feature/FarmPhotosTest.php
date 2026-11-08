<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\FarmPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The farm's own photographs, in every public slideshow.
 *
 * THE RULE the owner set: every slideshow shows every photograph, each in its
 * own random order, and the pages never look alike - so no two slideshows
 * open on the same photograph, or on two shots of the same bird. The order is
 * seeded from the session, so it holds steady for one visit - a page does not
 * reshuffle on Back, and the front page's Livewire re-renders do not swap the
 * hero photograph mid-look - and the next visitor gets a different one.
 *
 * Near-identical shots of one bird share a `look`, and two of them are never
 * shown back to back (counting the wrap from the last slide to the first),
 * so a slideshow never seems to show the same picture twice.
 */
final class FarmPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_slideshow_shows_every_photograph_exactly_once(): void
    {
        $all = array_column(FarmPhotos::PHOTOS, 'file');
        sort($all);

        foreach ([1, 2, 3, 99, 123456] as $seed) {
            $deal = FarmPhotos::deal($seed);

            $this->assertSame(FarmPhotos::SLOTS, array_keys($deal));

            foreach ($deal as $slot => $photos) {
                $files = array_column($photos, 'file');
                sort($files);

                $this->assertSame($all, $files, "slot {$slot}, seed {$seed}");
            }
        }
    }

    public function test_no_two_slideshows_open_on_the_same_photograph_or_the_same_look(): void
    {
        foreach (range(1, 200) as $seed) {
            $openers = array_map(fn (array $photos): array => $photos[0], FarmPhotos::deal($seed));

            $files = array_column($openers, 'file');
            $this->assertSame(array_values(array_unique($files)), $files, "seed {$seed}");

            $looks = array_values(array_filter(array_column($openers, 'look')));
            $this->assertSame(array_values(array_unique($looks)), $looks, "seed {$seed}");
        }
    }

    public function test_lookalike_photographs_are_never_shown_back_to_back(): void
    {
        foreach (range(1, 200) as $seed) {
            foreach (FarmPhotos::deal($seed) as $slot => $photos) {
                $count = count($photos);

                foreach ($photos as $index => $photo) {
                    $next = $photos[($index + 1) % $count];

                    $this->assertFalse(
                        $photo['look'] !== null && $photo['look'] === $next['look'],
                        "slot {$slot}, seed {$seed}: {$photo['file']} then {$next['file']}",
                    );
                }
            }
        }
    }

    public function test_the_slideshows_are_not_all_in_the_same_order(): void
    {
        $orders = array_map(fn (array $photos): string => implode(',', array_column($photos, 'file')), FarmPhotos::deal(5));

        $this->assertCount(count(FarmPhotos::SLOTS), array_unique($orders));
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
     * The four pages, rendered in one visit, each carry every farm photograph,
     * open their slideshows on five different ones, and show none of the old
     * stock photographs.
     */
    public function test_every_page_shows_every_photograph_and_no_two_slideshows_open_alike(): void
    {
        $this->withSession([FarmPhotos::SESSION_KEY => 314159]);

        $pages = [
            'home' => route('home'),
            'about' => route('public.about'),
            'visit' => route('public.visit'),
            'sign-in' => route('login'),
        ];

        $all = array_column(FarmPhotos::PHOTOS, 'file');
        sort($all);
        $openers = [];

        foreach ($pages as $page => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Slide 1 has a plain src; later slides are lazy-loaded through
            // @js(), which escapes the slashes (photos\/farm\/...).
            preg_match_all('#photos(?:\\\\)?/farm(?:\\\\)?/([a-z0-9-]+\.webp)#', $html, $matches);
            $files = array_values(array_unique($matches[1]));
            sort($files);

            $this->assertSame($all, $files, "{$page} is missing a farm photograph");
            $this->assertDoesNotMatchRegularExpression(
                '#photos(?:\\\\)?/(hero-farm|rooster-walking|rooster-portrait|rooster-profile|rooster-crowing|hens-fence|visit-field)\.webp#',
                $html,
                "{$page} still shows a stock photograph",
            );

            // Only each slideshow's opening photograph is a plain src.
            preg_match_all('#src="[^"]*/photos/farm/([a-z0-9-]+\.webp)"#', $html, $plain);
            $openers = [...$openers, ...$plain[1]];
        }

        $this->assertCount(count(FarmPhotos::SLOTS), $openers);
        $this->assertSame(array_values(array_unique($openers)), $openers, 'two slideshows open on the same photograph');
    }
}
