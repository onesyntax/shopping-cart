<?php

declare(strict_types=1);

namespace Tests\Support\Browser;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test-only middleware that forces a fixed cart owner on every request.
 *
 * The browser plugin closes the Playwright context (and its cookies) after each
 * test, so the Laravel session id does not survive between tests. Pinning the
 * `shopper_id` here means every request — across every test in a run — resolves
 * to the same {@see ContinuousSession::SHOPPER_ID}, so the cart built up in one
 * test is still there in the next. Registered at the end of the `web` group, so
 * the session has already been started by the time this runs.
 */
final class PinShopper
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            $request->session()->put('shopper_id', ContinuousSession::SHOPPER_ID);
        }

        return $next($request);
    }
}
