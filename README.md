# Shopping Cart

A shopping-cart application built on **Laravel 13 / PHP 8.3**, developed
test-first against executable Gherkin specifications and structured around
**Clean Architecture**.

It supports browsing a catalog, adding and removing items in a cart, and
checking out through four payment methods — card, bank deposit, cash on
delivery, and cash on hand. Money is handled exclusively in **integer cents**,
never as floats.

## Behaviour as the source of truth

Behaviour is specified up front as Gherkin feature files under `features/`,
and every scenario is an executable acceptance test (run with Behat). The
specs — not the code — are the authority on domain behaviour and validation
rules.

## Architecture

The project strictly follows **Clean Architecture**, organised
**component-first, layer-second** (Screaming Architecture): the top level of
`app/` says what the system *does*, and each component carries its own Clean
Architecture layers as sub-folders.

```
app/
  Cart/                  ─┐
  Catalog/                ├ core business capabilities
  Checkout/              ─┘
    Domain/                entities, value objects, domain events, repo + gateway interfaces
    UseCases/              one class per use case (orchestration)
      Inputs/              request/input DTOs
    Infrastructure/        the outer ring (framework-touching adapters):
      <Component>ServiceProvider.php   composition root (its bindings)
      Http/                controllers + form requests (inbound adapter)
      Persistence/         Eloquent + InMemory repositories (Models/ holds Eloquent records)
      Payment/             payment-gateway adapters (Checkout only)
    Tests/                 Unit/ (no framework) and Feature/ (real framework)
  Foundation/            cross-cutting shared kernel — Money, Quantity, ReferenceGenerator,
                         notifications, CurrentShopper — plus the cross-cutting test suites
```

### The Dependency Rule

Source-code dependencies point **only inward**:

- **Domain** depends on nothing. Pure PHP — no Laravel, no Eloquent, no
  facades, no I/O.
- **Application (UseCases)** depends only on Domain and on the interfaces it
  declares (repositories, gateways).
- **Interface Adapters** (controllers, validators, Eloquent repositories,
  payment adapters) depend on Application and Domain.
- **Frameworks & Drivers** (Laravel, Eloquent, routes, migrations, SDKs, the
  DB) sit in the outermost ring.

Boundaries are crossed only through interfaces defined in the inner layer;
data crosses as plain DTOs / value objects, never as Eloquent models or
`Request` objects. There is **no central `AppServiceProvider`** — each
component owns a `<Component>ServiceProvider` (listed in
`bootstrap/providers.php`) that registers only its own bindings.

The use cases per component:

- **Catalog** — `AddItemToCatalog`, `ListCatalog`
- **Cart** — `AddItemToCart`, `RemoveItemFromCart`, `ViewCart`
- **Checkout** — `CheckoutByCard`, `CheckoutByBankDeposit`,
  `CheckoutByCashOnDelivery`, `CheckoutByCashOnHand`, plus
  `OrderConfirmation` / `ViewOrderConfirmation`

## Getting started

Requires PHP 8.3+, Composer, and Node.

```bash
composer setup     # install deps, .env, key, migrate, npm install + build
composer dev       # serve app + queue worker + logs (pail) + vite, all at once
```

`composer dev` runs the server, queue listener, log tailer, and Vite
concurrently. The app then serves at `http://127.0.0.1:8000`.

## Testing & TDD

All production code is written **test-first** (Red → Green → Refactor). Tests
use **Pest** and are **co-located** with the component they cover under
`app/<Component>/Tests/`:

- `Tests/Unit/` — fast, isolated Domain + UseCase tests (no framework, no DB).
- `Tests/Feature/` — outer-layer tests against the real framework
  (`RefreshDatabase`).

Cross-cutting test code (the Behat acceptance suite, the end-to-end browser
journey, `ContainerBindingsTest`, the shared `TestCase`, helpers) lives under
`app/Foundation/Tests/`. The only thing left in top-level `tests/` is
`Pest.php`, Pest's bootstrap.

## Commands

| Command | What it does |
| --- | --- |
| `composer setup` | Install deps, create `.env`, generate key, migrate, build assets |
| `composer dev` | Run server + queue + logs + Vite concurrently |
| `vendor/bin/pest` (or `php artisan test`) | Run the Pest unit + feature suite |
| `composer test` | Clear config, then run the full test suite |
| `vendor/bin/behat` | Run the Gherkin acceptance suite |
| `composer test:browser` | Build assets, then run the Pest 4 / Playwright browser suite |
| `composer mutate` | Mutation testing (`pest --mutate --parallel --everything`) |
| `composer crap` | CRAP score analysis (coverage + complexity) |
| `composer dry` | Code-clone / DRY analysis |
| `vendor/bin/pint` | Code style (Laravel Pint) |
| `php artisan tinker` | REPL |

### Tooling notes

- **Money in cents.** All amounts use the `Money` value object and integer
  cents — never floats.
- **Behat / Laravel pin.** Behat caps `symfony/console` at ^7, so Laravel is
  pinned to **`laravel/framework 13.11.2`** (its `symfony/http-kernel` 8.0.x
  tolerates the Symfony 7.4 components Behat needs). Do **not** bump to
  13.12+ without revisiting this — it re-breaks Behat.
- `phpunit.xml` discovers tests via the `app/*/Tests/Unit` and
  `app/*/Tests/Feature` testsuite globs and excludes `app/*/Tests` from
  coverage/mutation.

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
