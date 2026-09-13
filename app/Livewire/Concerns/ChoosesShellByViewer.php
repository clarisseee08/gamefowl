<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Picks the page shell from who is looking, for screens that serve both the
 * public and the farm's own staff.
 *
 * The catalogue and a bird's page are now reachable without an account. Staff
 * reach the same URLs from inside the console, where every other screen has the
 * sidebar - so these cannot simply pick one shell and be right for both. A
 * visitor given the console rail sees navigation for screens that would bounce
 * them to a login form; a keeper given the bare public shell loses the only
 * navigation they have.
 *
 * SET IN render(), NEVER WITH #[Layout]. Livewire applies the attribute inside
 * its render hook, AFTER render() has returned, and it overwrites whatever the
 * view asked for. The two do not compose - the attribute silently wins - so a
 * component using this trait must not also carry a #[Layout].
 */
trait ChoosesShellByViewer
{
    protected function viewerShell(): string
    {
        return auth()->user()?->isInternal()
            ? 'layouts::app'
            : 'layouts::catalog';
    }
}
