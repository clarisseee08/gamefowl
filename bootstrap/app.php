<?php

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Behind a platform that terminates TLS for us (Render, Fly, most PaaS),
         * the container itself is spoken to over plain HTTP. Without this,
         * Laravel believes the request is insecure and writes http:// into every
         * generated URL - including Livewire's update endpoint, which the browser
         * then blocks as mixed content on an https page. The visible symptom is
         * an app that renders correctly and then does nothing at all when
         * clicked, with no server-side error to find.
         *
         * '*' is correct here rather than lax: the container is not publicly
         * routable, so the only thing that can reach it is the platform's own
         * proxy, whose egress IPs are neither fixed nor documented.
         */
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            // Signs out a user whose account is deactivated mid-session.
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
