<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Laravel 12's skeleton ships this class empty - it does NOT include
 * AuthorizesRequests, so calling $this->authorize() in a child controller
 * would be a fatal "call to undefined method". Since every controller in this
 * system is expected to authorize, the trait is added here once.
 */
abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;
}
