<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Identifies the shopper owning a cart and orders. The storefront has no
 * accounts, so each browser session gets a stable, opaque owner id stored in
 * the session. This is the one place HTTP "who is this" maps to the Domain's
 * ownerId string.
 */
final class CurrentShopper
{
    public static function id(Request $request): string
    {
        $id = $request->session()->get('shopper_id');

        if (! is_string($id)) {
            $id = (string) Str::uuid();
            $request->session()->put('shopper_id', $id);
        }

        return $id;
    }
}
