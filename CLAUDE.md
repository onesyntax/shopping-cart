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

### Proposed directory layout (PSR-4 `App\` → `app/`)

```
app/
  Domain/                  # entities, value objects, domain events, repo + gateway interfaces
    Cart/
    Catalog/
    Checkout/
    Shared/                # Money, Quantity, etc.
  Application/             # use cases (one class each) + input/output DTOs
    Cart/
    Checkout/
  Infrastructure/          # Eloquent repositories, payment gateways, framework adapters
    Persistence/Eloquent/
    Payment/
  Http/                    # controllers, form requests, presenters (thin; delegate to use cases)
  Providers/               # interface → implementation bindings
```

Keep Laravel artifacts (Eloquent models, migrations, routes, providers) in the
outer layers only. Eloquent models live in `Infrastructure`, not `Domain`.

## Working rules

- **Controllers stay thin.** They validate input, call a single use case, and
  hand the result to a presenter. No business logic in controllers.
- **No Eloquent in Domain or Application.** Persist through repository
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

Tests are written with **Pest**. Layout under `tests/`:

- `tests/Unit/` — fast, isolated tests for Domain and Application (no
  framework, no DB). These are where most TDD cycles happen.
- `tests/Feature/` — tests that exercise the outer layers (HTTP, Eloquent
  repositories) against the real framework.

Use Pest's `it()` / `test()` style and datasets for the validation tables in the
feature files (e.g. zero / negative / fractional quantities).

### Gherkin acceptance tests

The Gherkin specs in `features/` are **executable acceptance tests** and are the
source of truth for behaviour. Every scenario must be covered by a passing step
definition — a feature is not done until its scenarios are green. Run the full
Gherkin suite to check that all documented behaviours are covered and that none
have regressed.

> Tooling note: **Behat** (`vendor/bin/behat`) runs the `.feature` files.
> `tests/Behat/FeatureContext.php` is a thin catch-all that delegates every step
> to a regex-based step engine in `tests/Acceptance/ShoppingCartContext.php`.
> Configured in `behat.yml`.
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

- Pest and Behat are installed and green: Pest covers the Domain/Application unit
  tests under `tests/Unit`; Behat runs the `.feature` files (see the Tooling note
  above, and why Laravel is pinned to 13.11.2). `behat/gherkin` is no longer a
  direct dependency — it comes in transitively via `behat/behat`.
- Persistence is currently in-memory (`app/Infrastructure/Persistence/InMemory`),
  bound in `AppServiceProvider`. No scenario requires cross-request persistence;
  swap in Eloquent-backed repositories there when one does, without touching
  Domain or Application.
