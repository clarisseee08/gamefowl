<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BreedingRecord;
use App\Models\Broodcock;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The records half of the command palette (Ctrl+K): the palette lists the
 * screens itself and asks here for records matching what was typed.
 *
 * Each kind of record is offered only to someone whose policy lets them open
 * its list, so the palette never shows a result its page would refuse. Every
 * group is capped, and nothing is searched under two characters.
 */
class CommandSearchController extends Controller
{
    private const PER_GROUP = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        return response()->json(['results' => [
            ...$this->birds($user, $term),
            ...$this->matings($user, $term),
            ...$this->accounts($user, $term),
        ]]);
    }

    /** @return list<array{group: string, label: string, hint: string, url: string}> */
    private function birds(User $user, string $term): array
    {
        if (! $user->isInternal() || $user->cannot('viewAny', Broodcock::class)) {
            return [];
        }

        return Broodcock::query()
            ->search($term)
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get(['id', 'name', 'band_number', 'bloodline', 'is_external'])
            ->map(fn (Broodcock $bird): array => [
                'group' => 'Gamefowl',
                'label' => $bird->displayName(),
                'hint' => $bird->is_external ? 'Outside bird' : (string) $bird->bloodline,
                'url' => route('broodcocks.show', $bird),
            ])
            ->all();
    }

    /** @return list<array{group: string, label: string, hint: string, url: string}> */
    private function matings(User $user, string $term): array
    {
        if (! $user->isInternal() || $user->cannot('viewAny', BreedingRecord::class)) {
            return [];
        }

        $matchesParent = fn (Builder $bird): Builder => $bird->search($term);

        return BreedingRecord::query()
            ->with(['sire:id,name,band_number', 'dam:id,name,band_number'])
            ->where(fn (Builder $query) => $query
                ->whereHas('sire', $matchesParent)
                ->orWhereHas('dam', $matchesParent))
            ->latest('mating_date')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (BreedingRecord $record): array => [
                'group' => 'Matings',
                'label' => ($record->sire?->displayName() ?? 'Unknown sire').' × '.($record->dam?->displayName() ?? 'Unknown dam'),
                'hint' => $record->mating_date?->format('j M Y') ?? '',
                'url' => route('breeding.show', $record),
            ])
            ->all();
    }

    /** @return list<array{group: string, label: string, hint: string, url: string}> */
    private function accounts(User $user, string $term): array
    {
        if (! $user->isOwner()) {
            return [];
        }

        return User::query()
            ->where(fn (Builder $query) => $query
                ->whereLike('full_name', "%{$term}%", caseSensitive: false)
                ->orWhereLike('email', "%{$term}%", caseSensitive: false))
            ->orderBy('full_name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (User $account): array => [
                'group' => 'Users',
                'label' => $account->full_name,
                'hint' => $account->email,
                'url' => route('users.edit', $account),
            ])
            ->all();
    }
}
