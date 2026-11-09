<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Landing screen after sign-in. Only the farm's own people have accounts, so
 * everyone who reaches it gets the farm dashboard; the public use the
 * catalogue without signing in.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', ['user' => $request->user()]);
    }
}
