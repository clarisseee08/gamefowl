<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Profile\Edit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Leaving a page with changes that were not saved asks first.
 *
 * app.js stops a page change while any [data-unsaved-flag] inside a
 * [data-unsaved-guard] form is showing, and Livewire's wire:dirty shows the
 * flag while one of its target fields differs from what was saved. So what
 * must not break is the wiring: each form is marked, and each flag watches
 * every field of its form. A field missing from wire:target is a field whose
 * changes could be walked away from without a word.
 */
final class UnsavedChangesGuardTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        return Livewire::actingAs(User::factory()->staff()->create())->test(Edit::class)->html();
    }

    public function test_both_profile_forms_are_guarded(): void
    {
        $this->assertSame(2, substr_count($this->html(), 'data-unsaved-guard'));
    }

    public function test_the_details_flag_watches_every_detail_field(): void
    {
        preg_match('/wire:dirty wire:target="([^"]+)" data-unsaved-flag/', $this->html(), $match);

        $this->assertNotEmpty($match, 'The details form has no unsaved-changes flag.');
        $this->assertEqualsCanonicalizing(
            ['full_name', 'email', 'contact_number', 'address', 'position_choice', 'position_other'],
            explode(',', $match[1]),
        );
    }

    public function test_the_password_flag_watches_all_three_password_fields(): void
    {
        $this->assertStringContainsString(
            'wire:target="current_password,password,password_confirmation" data-unsaved-flag',
            $this->html(),
        );
    }

    public function test_the_details_flag_says_so_in_words(): void
    {
        $this->assertStringContainsString('Unsaved changes', $this->html());
    }
}
