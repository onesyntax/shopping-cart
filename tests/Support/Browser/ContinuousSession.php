<?php

declare(strict_types=1);

namespace Tests\Support\Browser;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Wires the browser suite to run as ONE continuous shopping session.
 *
 * The database is reset exactly once per run — never between tests — so state
 * accumulates: a cart filled in one test is still there in the next, and orders
 * pile up as the story plays out. Two things make that work despite Pest booting
 * a fresh application for every test:
 *
 *  1. A persistent sqlite *file* (not the default `:memory:`, which is destroyed
 *     when each test's connection closes), migrated a single time. The catalogue
 *     starts empty on purpose — the journey stocks it through the add-items page.
 *  2. A fixed cart owner ({@see PinShopper}), so the wiped per-test browser
 *     cookies don't hand each test a new, empty cart.
 *
 * Because tests build on each other, they must run in file order — do not enable
 * random test ordering for this suite. Serial only: the once-per-run guard and
 * the shared file would not hold under `--parallel`.
 */
final class ContinuousSession
{
    /** The single cart owner shared by every browser test. */
    public const SHOPPER_ID = 'browser-tests-shopper';

    private static bool $prepared = false;

    public static function boot(Application $app): void
    {
        $database = database_path('browser-tests.sqlite');

        // Re-point sqlite at the file every test (each fresh app reverts to the
        // phpunit :memory: default), but only build the schema + seed once.
        config(['database.connections.sqlite.database' => $database]);
        DB::purge('sqlite');

        if (! self::$prepared) {
            if (! file_exists($database)) {
                touch($database);
            }

            // One clean, empty slate at the start of the run; nothing is reset after.
            Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);

            self::$prepared = true;
        }

        // Append to the *kernel's* web group, not the router's: the kernel is
        // built lazily on the first visit() and its constructor re-syncs the
        // groups to the router, which would wipe a router-only push. Going
        // through the kernel forces it to exist and survives that sync.
        $app->make(Kernel::class)->appendMiddlewareToGroup('web', PinShopper::class);
    }
}
