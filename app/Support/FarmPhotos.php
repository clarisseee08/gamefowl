<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use LogicException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * The farm's own photographs, dealt out across the public slideshows.
 *
 * The owner's rule: no photograph appears on two pages, and which page gets
 * which is random. So the photographs are not listed per page. They are
 * shuffled and dealt, like cards, into SLOTS - one slot per slideshow - and
 * every photograph lands in exactly one.
 *
 * THE SHUFFLE IS SEEDED FROM THE SESSION. One visit keeps one deal: going
 * Home -> About -> Visit -> Sign in never shows the same bird twice, a page
 * does not reshuffle on Back, and the front page's Livewire re-renders do not
 * swap the hero photograph while someone is looking at it. The next visitor
 * gets a new seed, and with it a different deal.
 *
 * LOOK-ALIKES. A `look` marks shots of the same bird in nearly the same pose.
 * Two of them in one slideshow would read as the same picture twice, so they
 * are dealt first, while every slot still has room, and never into the same
 * one. (Two shots that were near-identical outright - imgs/gamefowl picture
 * 5033abe1 and 6c65f2a2 - were left out altogether.)
 *
 * The files are WebP, at most 1200px wide, with their camera metadata (GPS
 * included) stripped. They are served publicly and committed to a public
 * repository.
 */
final class FarmPhotos
{
    public const SESSION_KEY = 'farm_photos_seed';

    /**
     * How many photographs each slideshow receives. They add up to the number
     * of photographs, so every one is shown somewhere.
     *
     * @var array<string, int>
     */
    public const SLOTS = [
        'home' => 3,
        'home-visit' => 2,
        'about' => 2,
        'visit' => 2,
        'sign-in' => 2,
    ];

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
        if (! array_key_exists($slot, self::SLOTS)) {
            throw new InvalidArgumentException("There is no farm slideshow called [{$slot}].");
        }

        return array_map(fn (array $photo): array => [
            'src' => asset('photos/farm/'.$photo['file']),
            'alt' => $photo['alt'],
            'position' => $photo['position'],
        ], self::deal(self::seed())[$slot]);
    }

    /**
     * Deal every photograph into a slot. The same seed always gives the same
     * deal.
     *
     * @return array<string, list<array{file: string, alt: string, position: string, look: ?string}>>
     */
    public static function deal(int $seed): array
    {
        $randomizer = new Randomizer(new Mt19937($seed));
        $shuffled = $randomizer->shuffleArray(self::PHOTOS);

        $ordered = [
            ...array_values(array_filter($shuffled, fn (array $photo): bool => $photo['look'] !== null)),
            ...array_values(array_filter($shuffled, fn (array $photo): bool => $photo['look'] === null)),
        ];

        $deal = array_fill_keys(array_keys(self::SLOTS), []);

        foreach ($ordered as $photo) {
            $open = array_values(array_filter(
                array_keys(self::SLOTS),
                fn (string $slot): bool => count($deal[$slot]) < self::SLOTS[$slot]
                    && ($photo['look'] === null || ! in_array($photo['look'], array_column($deal[$slot], 'look'), true)),
            ));

            if ($open === []) {
                throw new LogicException("No slideshow has room for {$photo['file']}: SLOTS no longer fits PHOTOS.");
            }

            $deal[$open[$randomizer->getInt(0, count($open) - 1)]][] = $photo;
        }

        return $deal;
    }

    /** This visit's seed, chosen on its first slideshow and kept for the rest of it. */
    private static function seed(): int
    {
        return (int) session()->remember(self::SESSION_KEY, fn (): int => random_int(1, 2_147_483_647));
    }
}
