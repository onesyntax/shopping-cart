# CLAUDE.md

Guidance for working in this repository.

## Project

A shopping-cart application built on **Laravel 13 / PHP 8.3**. Behaviour is
specified up front as Gherkin feature files under `features/` (catalog, cart,
add/remove items, and checkout via card, bank deposit, cash on delivery, and
cash on hand). Money is handled in **integer cents**, never floats.

## Architecture: Clean Architecture is mandatory

This project **strictly follows Clean Architecture**. Every change must respect
the layering and the dependency rule below. When a framework convenience
conflicts with these rules, the rules win.

### Layers (innermost → outermost)

1. **Domain (Entities)** — Enterprise business rules. Pure PHP: entities, value
   objects (e.g. `Money`, `Quantity`), domain events, and repository
   *interfaces*. No Laravel, no Eloquent, no I/O, no static facades.
2. **Application (Use Cases)** — Application-specific business rules. One class
   per use case (e.g. `AddItemToCart`, `RemoveItemFromCart`, `CheckoutByCard`),
   orchestrating domain objects. Depends only on Domain and on interfaces
   (repositories, gateways) it declares. No HTTP, no Eloquent, no facades.
3. **Interface Adapters** — Controllers, request validators, presenters/response
   DTOs, Eloquent-backed repository *implementations*, and payment-gateway
   adapters. Converts between the outside world and use cases.
4. **Frameworks & Drivers** — Laravel itself, Eloquent, routes, migrations,
   the HTTP kernel, third-party SDKs, the database.

### The Dependency Rule

Source-code dependencies point **only inward**. Inner layers know nothing about
outer layers.

- Domain depends on nothing.
- Application depends only on Domain.
- Adapters depend on Application and Domain.
- Nothing in Domain or Application may reference `Illuminate\*`, Eloquent
  models, facades (`DB`, `Auth`, `Cache`, ...), `request()`, `config()`,
  `env()`, or any global helper.
- Cross a boundary only through an **interface** defined in the inner layer.
  Outer layers implement those interfaces; bind them in a service provider.
- Data crosses boundaries as plain DTOs / value objects, never as Eloquent
  models or `Request` objects.

### Directory layout — Screaming Architecture (PSR-4 `App\` → `app/`)

Organised **component-first, layer-second**: the top level of `app/` screams
what the system *does* (Cart, Catalog, Checkout), not what framework it uses.
Each component carries its own Clean Architecture layers as sub-folders, so the
dependency rule stays visible *inside* every component.

```
app/
  Cart/                    # ─┐
  Catalog/                 #  ├ core business capabilities (the headline)
  Checkout/                # ─┘
    Domain/                #   entities, value objects, domain events, repo + gateway interfaces
    UseCases/              #   one class per use case (orchestration)
      Inputs/              #     request/input DTOs for those use cases
    Infrastructure/        #   the outer ring (all framework-touching adapters):
      <Component>ServiceProvider.php   #   the component's composition root (its bindings)
      Http/                #     controllers + form requests (inbound/driving adapter)
      Persistence/         #     Eloquent + InMemory repositories
        Models/            #       Eloquent records
      Payment/             #     payment-gateway adapters (Checkout only)
  Foundation/              # cross-cutting support, not a business capability —
                           # the shared kernel and notifications merged into one component:
    Domain/                #   Money, Quantity, ReferenceGenerator, DomainException,
                           #   Notification, NotificationKind, NotificationLine, Notifier
    Infrastructure/        #   DatabaseReferenceGenerator, SequentialReferenceGenerator, RecordingNotifier
      FoundationServiceProvider.php    #   composition root for shared/notification bindings
      Http/                #     CurrentShopper
      Persistence/Models/  #     ReferenceSequenceRecord
```

There is **no central `AppServiceProvider`**. Each component owns a
`<Component>ServiceProvider` in its `Infrastructure/` that registers only its
own interface → implementation bindings; they are listed in
`bootstrap/providers.php`. No provider knows about another component's wiring.

Inner→outer dependency rule is unchanged: `Domain` depends on nothing,
`UseCases` only on `Domain`, `Infrastructure` (incl. `Http`) on the inner
layers. Per the Hexagonal split, `Infrastructure/Http` is the *inbound/driving*
adapter (it calls *into* use cases) and lives alongside the *outbound/driven*
adapters (`Persistence`, `Payment`) under one `Infrastructure/` outer ring.

Keep Laravel artifacts (Eloquent models, migrations, routes, providers) in the
outer layers only. Eloquent models live under `<Component>/Infrastructure/
Persistence/Models`, never in `Domain`.

## Working rules

- **Controllers stay thin.** They validate input, call a single use case, and
  hand the result to a presenter. No business logic in controllers.
- **No Eloquent in Domain or UseCases.** Persist through repository
  interfaces; implement them with Eloquent in `Infrastructure`.
- **Money in cents.** Use a `Money` value object; never store or compute money
  as a float.
- **Behaviour is driven by `features/`.** Treat the Gherkin scenarios as the
  source of truth for domain behaviour and validation rules.
- Before adding a class, decide its layer first, then ensure its dependencies
  point inward. If you can't, the design is wrong — fix the design, not the rule.

## Testing & TDD

### TDD is mandatory

All production code is written **test-first**, following strict Red → Green →
Refactor:

1. **Red** — write the smallest failing test for the next bit of behaviour, and
   run it to confirm it fails for the *right* reason. Never write production
   code without a failing test demanding it.
2. **Green** — write the minimum code to make that test pass. Nothing more.
3. **Refactor** — clean up code and tests while the suite stays green.

Rules:

- No production code without a failing test first.
- Commit only on green. Keep steps small — one behaviour per cycle.
- Test behaviour through public interfaces, not implementation details.
- Drive the layers from the inside out: unit-test Domain and Application use
  cases in isolation (no DB, no HTTP) using fakes/in-memory implementations of
  the repository interfaces; reserve the database and HTTP for the outer layers.

### Pest for tests

Tests are written with **Pest** and **co-located with the component they
cover**, under `app/<Component>/Tests/`:

- `app/<Component>/Tests/Unit/` — fast, isolated tests for that component's
  Domain and UseCases (no framework, no DB). Most TDD cycles happen here.
- `app/<Component>/Tests/Feature/` — tests that exercise the component's outer
  layers (HTTP, Eloquent repositories) against the real framework
  (`uses(RefreshDatabase::class)`).

`phpunit.xml` discovers these via the `app/*/Tests/Unit` and `app/*/Tests/Feature`
testsuite globs, and `<source>` excludes `app/*/Tests` so coverage and mutation
never target test code. The framework base case (`App\Foundation\Tests\TestCase` +
`withoutVite`) is bound to the Feature directories in `tests/Pest.php`.

Genuinely **cross-cutting** test code lives in **`app/Foundation/Tests/`** (the
shared/cross-cutting component): the Behat acceptance suite (`Behat/`,
`Acceptance/`), the end-to-end `Browser/` journey, `ContainerBindingsTest`
(asserts every component's bindings), the shared `TestCase`, and `Support/`
helpers — all namespaced `App\Foundation\Tests\…`.

The **only** thing left under top-level `tests/` is `Pest.php` — Pest's
bootstrap/config (global helpers, the `pest()->extend(...)->in(...)` bindings).
Pest pins this to `tests/Pest.php` by convention (the same kind of tool entry-point
constraint as `phpunit.xml` at the project root and `behat.yml`); it cannot move,
so it stays as the lone bootstrap and points `->in()` at the component test dirs.

Use Pest's `it()` / `test()` style and datasets for the validation tables in the
feature files (e.g. zero / negative / fractional quantities).

### Gherkin acceptance tests

The Gherkin specs in `features/` are **executable acceptance tests** and are the
source of truth for behaviour. Every scenario must be covered by a passing step
definition — a feature is not done until its scenarios are green. Run the full
Gherkin suite to check that all documented behaviours are covered and that none
have regressed.

> Tooling note: **Behat** (`vendor/bin/behat`) runs the `.feature` files.
> `app/Foundation/Tests/Behat/FeatureContext.php` is a thin catch-all that
> delegates every step to a regex-based step engine in
> `app/Foundation/Tests/Acceptance/ShoppingCartContext.php`. The context class
> (`App\Foundation\Tests\Behat\FeatureContext`) is wired in `behat.yml`.
>
> Behat caps `symfony/console` at ^7, so it cannot run against Symfony 8. Laravel
> is therefore pinned to **`laravel/framework 13.11.2`**, whose
> `symfony/http-kernel` is 8.0.x and tolerates the Symfony 7.4 components Behat
> needs. Do **not** bump Laravel to 13.12+ (which pulls `symfony/http-kernel`
> 8.1 and re-breaks Behat) without revisiting this.

## Commands

- Run the Pest unit suite (Domain + Application): `vendor/bin/pest` (or `php artisan test`)
- Run the Gherkin acceptance suite (Behat): `vendor/bin/behat`
- Code style: `vendor/bin/pint`
- Tinker / REPL: `php artisan tinker`

## Setup status

- Pest and Behat are installed and green: Pest covers each component's unit and
  feature tests under `app/<Component>/Tests`; Behat runs the `.feature` files
  (see the Tooling note above, and why Laravel is pinned to 13.11.2).
  `behat/gherkin` is no longer a direct dependency — it comes in transitively via
  `behat/behat`.
- Repositories live per component under `<Component>/Infrastructure/Persistence`
  (both `Eloquent…` and `InMemory…` implementations); each component's
  `<Component>ServiceProvider` binds its Eloquent-backed ones so the cart,
  catalog and orders persist across HTTP requests. Swap implementations there
  without touching Domain or UseCases.
