<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Broodcock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Landing screen after sign-in.
 *
 * Customers do not get the farm dashboard - they are sent straight to the
 * read-only catalogue, which is the only thing their role can act on.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'totalBroodcocks' => $user->isInternal() ? Broodcock::count() : null,
        ]);
    }
}
