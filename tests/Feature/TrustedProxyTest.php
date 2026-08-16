<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The app is deployed behind a proxy that terminates TLS, so the container
 * itself only ever sees plain HTTP.
 *
 * This is worth a test because the failure is silent. Nothing errors: pages
 * render, the server logs stay clean, and the only symptom is that Livewire
 * stops responding to clicks, because its update endpoint was generated as
 * http:// on an https page and the browser blocked it as mixed content. That
 * is a very expensive thing to debug live, so it is pinned here instead.
 */
final class TrustedProxyTest extends TestCase
{
    public function test_it_treats_a_forwarded_https_request_as_secure(): void
    {
        $this->get('/up', ['X-Forwarded-Proto' => 'https']);

        $this->assertTrue(
            $this->app['request']->isSecure(),
            'The proxy header was ignored, so Laravel will generate http:// URLs behind TLS.',
        );
    }

    public function test_it_generates_https_urls_for_a_forwarded_request(): void
    {
        $this->get('/up', ['X-Forwarded-Proto' => 'https']);

        $this->assertStringStartsWith(
            'https://',
            $this->app['request']->url(),
            'Generated URLs must inherit the scheme the browser actually used.',
        );
    }

    public function test_a_plain_request_is_still_reported_as_insecure(): void
    {
        $this->get('/up');

        $this->assertFalse(
            $this->app['request']->isSecure(),
            'Trusting proxies must not make every request look secure.',
        );
    }
}
