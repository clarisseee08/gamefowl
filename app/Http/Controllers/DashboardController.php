<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Landing screen after sign-in.
 *
 * Customers do not get the farm dashboard - it is entirely made of internal
 * figures (mortality, fertility rates, pedigree gaps) that they must not see.
 * They are sent to the catalogue, which is the only screen their role can
 * actually act on. This is a courtesy redirect; the Policies are what stop
 * them reaching the dashboard's data.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isCustomer()) {
            return redirect()->route('catalog.index');
        }

        return view('dashboard', ['user' => $user]);
    }
}
