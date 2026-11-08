<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use LogicException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The farm's own photographs, in every public slideshow.
 *
 * The owner's rule: every slideshow shows every photograph, in its own random
 * order, and no two pages look alike. So each slideshow - one SLOT each - gets
 * its own shuffle, and the five open on five different photographs, never two
 * shots of the same bird.
 *
 * THE SHUFFLE IS SEEDED FROM THE SESSION. One visit keeps one order: a page
 * does not reshuffle on Back, and the front page's Livewire re-renders do not
 * swap the hero photograph while someone is looking at it. The next visitor
 * gets a new seed, and with it new orders.
 *
 * LOOK-ALIKES. A `look` marks shots of the same bird in nearly the same pose.
 * Two of them back to back would read as the same picture twice, so they are
 * never neighbours - counting the wrap from the last slide to the first. (Two
 * shots that were near-identical outright - imgs/gamefowl picture 5033abe1
 * and 6c65f2a2 - were left out altogether.)
 *
 * The files are WebP, at most 1200px wide, with their camera metadata (GPS
 * included) stripped. They are served publicly and committed to a public
 * repository.
 */
final class FarmPhotos
{
    public const SESSION_KEY = 'farm_photos_seed';

    /**
     * The slideshows that show the farm's photographs.
     *
     * @var list<string>
     */
    public const SLOTS = ['home', 'home-visit', 'about', 'visit', 'sign-in'];

    /**
     * `position` is the object-position for the crop. Every photograph is
     * portrait, and the front page's frames are landscape, so it marks where
     * the bird stands.
     *
     * @var list<array{file: string, alt: string, position: string, look: ?string}>
     */
    public const PHOTOS = [
        ['file' => 'rooster-tin-fence.webp', 'alt' => 'A red and black rooster standing in front of a rusted tin fence', 'position' => '55% 68%', 'look' => null],
        ['file' => 'hen-in-leaves.webp', 'alt' => 'A brown hen walking past green bushes', 'position' => '55% 55%', 'look' => null],
        ['file' => 'white-rooster-fence.webp', 'alt' => 'A white rooster with a red comb perched on a tin fence', 'position' => '50% 50%', 'look' => null],
        ['file' => 'rooster-perch-banana.webp', 'alt' => 'A red rooster on a bamboo perch in a grassy yard, banana trees behind', 'position' => '55% 38%', 'look' => null],
        ['file' => 'rooster-in-hand.webp', 'alt' => 'A red rooster held in a handler\'s hands', 'position' => '45% 45%', 'look' => null],
        ['file' => 'rooster-bamboo-gate.webp', 'alt' => 'A red and black rooster standing on gravel beside a bamboo gate', 'position' => '50% 60%', 'look' => null],
        ['file' => 'grey-hen-bamboo.webp', 'alt' => 'A grey hen walking past a bamboo pen', 'position' => '50% 55%', 'look' => 'grey-hen'],
        ['file' => 'white-rooster-grass.webp', 'alt' => 'A white rooster with red-brown wings standing on grass', 'position' => '45% 58%', 'look' => null],
        ['file' => 'grey-hen-foraging.webp', 'alt' => 'A grey hen pecking at the ground beside a bamboo pen', 'position' => '40% 55%', 'look' => 'grey-hen'],
        ['file' => 'silver-rooster-leaning.webp', 'alt' => 'A silver-hackled rooster leaning forward on a bamboo perch', 'position' => '50% 55%', 'look' => 'silver-rooster'],
        ['file' => 'silver-rooster-perch.webp', 'alt' => 'A silver-hackled rooster with a black tail standing on a bamboo perch', 'position' => '50% 55%', 'look' => 'silver-rooster'],
    ];

    /**
     * This visit's slides for one slideshow, ready for <x-ui.slideshow>.
     *
     * @return list<array{src: string, alt: string, position: string}>
     */
    public static function slides(string $slot): array
    {
        if (! in_array($slot, self::SLOTS, true)) {
            throw new InvalidArgumentException("There is no farm slideshow called [{$slot}].");
        }

        return array_map(fn (array $photo): array => [
            'src' => asset('photos/farm/'.$photo['file']),
            'alt' => $photo['alt'],
            'position' => $photo['position'],
        ], self::deal(self::seed())[$slot]);
    }

    /**
     * Every slideshow's order. The same seed always gives the same orders.
     *
     * Each slideshow opens on its own photograph - five different ones, no
     * two of the same look - followed by the rest in a fresh shuffle,
     * reshuffled until no two look-alikes are neighbours.
     *
     * @return array<string, list<array{file: string, alt: string, position: string, look: ?string}>>
     */
    public static function deal(int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $openers = self::openers($randomizer);
        $deal = [];

        foreach (self::SLOTS as $index => $slot) {
            $opener = $openers[$index];
            $rest = array_values(array_filter(self::PHOTOS, fn (array $photo): bool => $photo['file'] !== $opener['file']));

            for ($attempt = 1; ; $attempt++) {
                $order = [$opener, ...$randomizer->shuffleArray($rest)];

                if (! self::hasNeighbouringLookalikes($order)) {
                    break;
                }

                if ($attempt === 200) {
                    throw new LogicException("Could not order the {$slot} slideshow without look-alikes side by side.");
                }
            }

            $deal[$slot] = $order;
        }

        return $deal;
    }

    /**
     * One opening photograph per slideshow: all different, and no two of the
     * same look, so no two pages greet a visitor with what reads as the same
     * picture.
     *
     * @return list<array{file: string, alt: string, position: string, look: ?string}>
     */
    private static function openers(Randomizer $randomizer): array
    {
        $openers = [];
        $looks = [];

        foreach ($randomizer->shuffleArray(self::PHOTOS) as $photo) {
            if ($photo['look'] !== null && in_array($photo['look'], $looks, true)) {
                continue;
            }

            $openers[] = $photo;
            $looks[] = $photo['look'];

            if (count($openers) === count(self::SLOTS)) {
                return $openers;
            }
        }

        throw new LogicException('There are fewer distinct photographs than slideshows.');
    }

    /**
     * @param  list<array{file: string, alt: string, position: string, look: ?string}>  $order
     */
    private static function hasNeighbouringLookalikes(array $order): bool
    {
        $count = count($order);

        foreach ($order as $index => $photo) {
            $next = $order[($index + 1) % $count];

            if ($photo['look'] !== null && $photo['look'] === $next['look']) {
                return true;
            }
        }

        return false;
    }

    /** This visit's seed, chosen on its first slideshow and kept for the rest of it. */
    private static function seed(): int
    {
        return (int) session()->remember(self::SESSION_KEY, fn (): int => random_int(1, 2_147_483_647));
    }
}
