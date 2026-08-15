<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Broodcocks\Index;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Both paginators in this app are OVERRIDDEN views:
 *   resources/views/vendor/livewire/tailwind.blade.php   (every Livewire table)
 *   resources/views/vendor/pagination/tailwind.blade.php (plain Laravel paginators)
 *
 * They were replaced because the stock versions ship a white ground, grey
 * borders and dark: variants from the default palette - none of which exist in
 * this design system - and the paginator renders on six screens, making it the
 * most-repeated component in the app.
 *
 * Restyling a vendor view means owning its BEHAVIOUR too. If a wire:click is
 * dropped or misnamed while re-writing the markup, every table in the app
 * silently stops paginating and no other test notices, because the rest of the
 * suite only ever looks at page one. That is what these assert.
 */
final class PaginationViewTest extends TestCase
{
    use RefreshDatabase;

    private function internalUser(): User
    {
        return User::factory()->create(['role' => 'owner', 'is_active' => true]);
    }

    public function test_the_livewire_paginator_emits_the_wire_directives_it_needs(): void
    {
        Broodcock::factory()->count(30)->create();

        $html = Livewire::actingAs($this->internalUser())
            ->test(Index::class)
            ->html();

        // The three calls WithPagination exposes. A rewrite that loses any of
        // them leaves a paginator that renders correctly and does nothing.
        $this->assertStringContainsString("wire:click=\"nextPage('page')\"", $html);
        $this->assertStringContainsString("wire:click=\"gotoPage(2, 'page')\"", $html);
        $this->assertStringContainsString('Pagination Navigation', $html);
    }

    public function test_the_livewire_paginator_actually_advances_a_page(): void
    {
        Broodcock::factory()->count(30)->create();

        $component = Livewire::actingAs($this->internalUser())
            ->test(Index::class);

        $firstPage = $component->html();

        $component->call('nextPage', 'page');
        $secondPage = $component->html();

        $this->assertNotSame($firstPage, $secondPage,
            'Calling nextPage did not change the rendered output - pagination is not working.');

        // The current-page marker must move with it, or the paginator is lying
        // about where the user is.
        $this->assertStringContainsString('aria-current="page"', $secondPage);
    }

    public function test_neither_paginator_reintroduces_the_stock_palette(): void
    {
        foreach ([
            'resources/views/vendor/livewire/tailwind.blade.php',
            'resources/views/vendor/pagination/tailwind.blade.php',
        ] as $path) {
            $body = file_get_contents(base_path($path));

            // Deliberately assembled rather than written literally: Tailwind
            // scans this repo for content and would compile any class name
            // spelled out here, which is the exact bug this guards against.
            foreach (['gr'.'ay', 'sl'.'ate', 'zi'.'nc', 'st'.'one'] as $family) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\b(bg|text|border|ring|divide)-'.$family.'-\d{2,3}\b/',
                    $body,
                    "{$path} reintroduces the stock {$family} palette, which this design system does not define."
                );
            }

            $this->assertStringNotContainsString('dark:', $body,
                "{$path} carries dark: variants; this app has a single committed light world.");
        }
    }
}
