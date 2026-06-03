<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use App\Application\Cart\AddItemToCart;
use App\Application\Cart\AddItemToCartInput;
use App\Application\Cart\RemoveItemFromCart;
use App\Application\Cart\RemoveItemFromCartInput;
use App\Application\Catalog\AddItemToCatalog;
use App\Application\Catalog\AddItemToCatalogInput;
use App\Application\Checkout\CheckoutByBankDeposit;
use App\Application\Checkout\CheckoutByBankDepositInput;
use App\Application\Checkout\CheckoutByCard;
use App\Application\Checkout\CheckoutByCardInput;
use App\Application\Checkout\CheckoutByCashOnDelivery;
use App\Application\Checkout\CheckoutByCashOnHand;
use App\Application\Checkout\CheckoutInput;
use App\Domain\Catalog\Item;
use App\Domain\Checkout\Order;
use App\Domain\Checkout\OrderStatus;
use App\Domain\Notification\Notification;
use App\Domain\Notification\NotificationKind;
use App\Domain\Shared\Money;
use App\Infrastructure\Notification\RecordingNotifier;
use App\Infrastructure\Payment\FakeCardPaymentGateway;
use App\Infrastructure\Persistence\InMemory\InMemoryCartRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryInvoiceRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryItemRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryOrderRepository;
use App\Infrastructure\Shared\SequentialReferenceGenerator;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

/**
 * Step definitions for the Gherkin feature files. Each scenario gets a fresh
 * context (fresh in-memory adapters and use cases). Steps are matched to
 * handlers by regular expression, Behat-style, so And/But inherit the previous
 * keyword's pool.
 */
final class ShoppingCartContext
{
    private InMemoryItemRepository $items;

    private InMemoryCartRepository $carts;

    private InMemoryOrderRepository $orders;

    private InMemoryInvoiceRepository $invoices;

    private RecordingNotifier $notifier;

    private FakeCardPaymentGateway $gateway;

    private AddItemToCatalog $addItemToCatalog;

    private AddItemToCart $addItemToCart;

    private RemoveItemFromCart $removeItemFromCart;

    private CheckoutByCard $checkoutByCard;

    private CheckoutByBankDeposit $checkoutByBankDeposit;

    private CheckoutByCashOnDelivery $checkoutByCashOnDelivery;

    private CheckoutByCashOnHand $checkoutByCashOnHand;

    /** The exception thrown by the most recent "When" step, if any. */
    private ?Throwable $error = null;

    /** The shopper the current pronoun ("her"/"his") refers to. */
    private string $currentUser = 'alice';

    /** @var array<int, array{0: string, 1: callable}> */
    private array $steps = [];

    public function __construct()
    {
        $references = new SequentialReferenceGenerator;

        $this->items = new InMemoryItemRepository;
        $this->carts = new InMemoryCartRepository;
        $this->orders = new InMemoryOrderRepository;
        $this->invoices = new InMemoryInvoiceRepository;
        $this->notifier = new RecordingNotifier;
        $this->gateway = new FakeCardPaymentGateway($references);

        $this->addItemToCatalog = new AddItemToCatalog($this->items);
        $this->addItemToCart = new AddItemToCart($this->items, $this->carts);
        $this->removeItemFromCart = new RemoveItemFromCart($this->items, $this->carts);
        $this->checkoutByCard = new CheckoutByCard(
            $this->carts, $this->orders, $this->invoices, $this->gateway, $this->notifier, $references,
        );
        $this->checkoutByBankDeposit = new CheckoutByBankDeposit(
            $this->carts, $this->orders, $this->notifier, $references,
        );
        $this->checkoutByCashOnDelivery = new CheckoutByCashOnDelivery(
            $this->carts, $this->orders, $this->invoices, $this->notifier, $references,
        );
        $this->checkoutByCashOnHand = new CheckoutByCashOnHand(
            $this->carts, $this->orders, $this->invoices, $this->notifier, $references,
        );

        $this->registerSteps();
    }

    /**
     * Execute a single step given its raw text (no keyword) and optional table.
     * The Behat FeatureContext delegates every step here.
     */
    public function run(string $text, ?TableNode $table): void
    {
        foreach ($this->steps as [$pattern, $handler]) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $handler($matches, $table);

                return;
            }
        }

        throw new RuntimeException("Undefined step: {$text}");
    }

    private function item(string $name, string $description, int $cents): Item
    {
        return new Item($name, $description, Money::ofCents($cents));
    }

    private function step(string $pattern, callable $handler): void
    {
        $this->steps[] = [$pattern, $handler];
    }

    private function registerSteps(): void
    {
        // ---- Given / background --------------------------------------------

        $this->step('/^the following items are available:$/', function ($m, ?TableNode $table) {
            foreach ($table->getHash() as $row) {
                $this->items->save($this->item($row['name'], $row['description'], (int) $row['price (cents)']));
            }
        });

        $this->step('/^a user named (\w+) has an empty cart$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->carts->forOwner($this->currentUser);
        });

        $this->step('/^the catalog already contains an item named "([^"]*)"$/', function ($m) {
            $this->items->save($this->item($m[1], 'An existing catalog listing', 10000));
        });

        $this->step(
            '/^(?:a user named )?(\w+) has (\d+) units? of (.+?) in (?:her|his) cart$/',
            function ($m) {
                $this->currentUser = $this->idOf($m[1]);
                $this->addItemToCart->handle(new AddItemToCartInput($this->currentUser, $m[3], (int) $m[2]));
            },
        );

        // ---- When: catalog --------------------------------------------------

        $this->step('/^(\w+) adds the following item:$/', function ($m, ?TableNode $table) {
            $row = $table->getHash()[0];
            $this->attempt(fn () => $this->addItemToCatalog->handle(new AddItemToCatalogInput(
                $row['name'] ?? '',
                $row['description'] ?? '',
                $row['price'] ?? '',
            )));
        });

        // ---- When: cart -----------------------------------------------------

        $this->step('/^(\w+) adds (-?[\d.]+) (?:more )?units? of (.+?) to (?:her|his) cart$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $quantity = $this->numericQuantity($m[2]);
            $this->attempt(fn () => $this->addItemToCart->handle(
                new AddItemToCartInput($this->currentUser, $m[3], $quantity),
            ));
        });

        $this->step('/^(\w+) removes (.+?) from (?:her|his) cart$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->removeItemFromCart->handle(
                new RemoveItemFromCartInput($this->currentUser, $m[2]),
            ));
        });

        // ---- When: checkout -------------------------------------------------

        $this->step('/^(\w+) checks out (?:her|his) cart paying with a valid card$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->checkoutByCard->handle(
                new CheckoutByCardInput($this->currentUser, FakeCardPaymentGateway::VALID),
            ));
        });

        $this->step('/^(\w+) checks out (?:her|his) cart paying with a card that the bank declines$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->checkoutByCard->handle(
                new CheckoutByCardInput($this->currentUser, FakeCardPaymentGateway::DECLINED),
            ));
        });

        $this->step('/^(\w+) checks out (?:her|his) cart paying with a card the gateway cannot reach$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->checkoutByCard->handle(
                new CheckoutByCardInput($this->currentUser, FakeCardPaymentGateway::UNREACHABLE),
            ));
        });

        $this->step(
            '/^(\w+) checks out (?:her|his) cart paying by bank deposit with reference "([^"]*)" made on "([^"]*)"$/',
            function ($m) {
                $this->currentUser = $this->idOf($m[1]);
                $this->attempt(fn () => $this->checkoutByBankDeposit->handle(
                    new CheckoutByBankDepositInput($this->currentUser, $m[2], $m[3]),
                ));
            },
        );

        $this->step('/^(\w+) checks out (?:her|his) cart to pay cash on delivery$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->checkoutByCashOnDelivery->handle(new CheckoutInput($this->currentUser)));
        });

        $this->step('/^(\w+) checks out (?:her|his) cart paying cash at the counter$/', function ($m) {
            $this->currentUser = $this->idOf($m[1]);
            $this->attempt(fn () => $this->checkoutByCashOnHand->handle(new CheckoutInput($this->currentUser)));
        });

        $this->step('/^the catalog price of (.+?) is later changed to "([^"]*)"$/', function ($m) {
            $existing = $this->items->findByName($m[1]);
            $this->items->save($this->item($m[1], $existing->description, Money::fromString($m[2])->cents));
        });

        $this->registerThenSteps();
    }

    private function registerThenSteps(): void
    {
        // ---- Catalog --------------------------------------------------------

        $this->step('/^"([^"]*)" is available in the catalog for "([^"]*)"$/', function ($m) {
            $item = $this->items->findByName($m[1]);
            Assert::assertNotNull($item, "Item {$m[1]} is not in the catalog");
            Assert::assertSame($m[2], $item->price->format());
        });

        // ---- Rejections -----------------------------------------------------

        $this->step('/^(?:adding|removing) the item is rejected because (.+)$/', function ($m) {
            $this->assertRejectedBecause($m[1]);
        });

        $this->step('/^checking out is rejected because (.+)$/', function ($m) {
            $this->assertRejectedBecause($m[1]);
        });

        // ---- Cart state -----------------------------------------------------

        $this->step('/^(?:(\w+)\'s|her|his) cart (?:still )?contains (\d+) units? of (.+)$/', function ($m) {
            $cart = $this->carts->forOwner($this->userFrom($m[1] ?? ''));
            Assert::assertSame((int) $m[2], $cart->quantityOf($m[3]));
        });

        $this->step('/^(?:(\w+)\'s|her|his) cart (?:still )?has (\d+) lines?$/', function ($m) {
            $cart = $this->carts->forOwner($this->userFrom($m[1] ?? ''));
            Assert::assertSame((int) $m[2], $cart->lineCount());
        });

        $this->step('/^(?:(\w+)\'s|her|his) cart is empty$/', function ($m) {
            $cart = $this->carts->forOwner($this->userFrom($m[1] ?? ''));
            Assert::assertTrue($cart->isEmpty(), 'Expected the cart to be empty');
        });

        $this->step('/^(?:(\w+)\'s|her|his) cart total is "([^"]*)"$/', function ($m) {
            $cart = $this->carts->forOwner($this->userFrom($m[1] ?? ''));
            Assert::assertSame($m[2], $cart->total()->format());
        });

        $this->step('/^(\w+)\'s cart still contains (\d+) units? of (.+)$/', function ($m) {
            $cart = $this->carts->forOwner($this->idOf($m[1]));
            Assert::assertSame((int) $m[2], $cart->quantityOf($m[3]));
        });

        $this->step('/^(\w+) still contains (\d+) units? of (.+)$/', function ($m) {
            $cart = $this->carts->forOwner($this->idOf($m[1]));
            Assert::assertSame((int) $m[2], $cart->quantityOf($m[3]));
        });

        // ---- Order state ----------------------------------------------------

        $this->step('/^no order is placed for (?:her|him)$/', function () {
            Assert::assertNull($this->orders->latestForOwner($this->currentUser));
        });

        $this->step('/^(?:her|his) latest order is paid$/', function () {
            Assert::assertTrue($this->latestOrder()->isPaid());
        });

        $this->step('/^(?:her|his) latest order was paid by card$/', function () {
            $order = $this->latestOrder();
            Assert::assertTrue($order->isPaid());
            Assert::assertSame('card', $order->paymentMethod->label());
        });

        $this->step('/^(?:her|his) latest order is awaiting payment confirmation$/', function () {
            Assert::assertSame(OrderStatus::AwaitingDepositConfirmation, $this->latestOrder()->status);
        });

        $this->step('/^(?:her|his) latest order is awaiting payment on delivery$/', function () {
            Assert::assertSame(OrderStatus::AwaitingPaymentOnDelivery, $this->latestOrder()->status);
        });

        $this->step('/^(?:her|his) latest order\'s payment method is (.+)$/', function ($m) {
            Assert::assertSame($m[1], $this->latestOrder()->paymentMethod->label());
        });

        $this->step('/^(?:her|his) latest order contains (\d+) units? of (.+)$/', function ($m) {
            Assert::assertSame((int) $m[1], $this->latestOrder()->quantityOf($m[2]));
        });

        $this->step('/^(?:her|his) latest order has (\d+) lines?$/', function ($m) {
            Assert::assertSame((int) $m[1], $this->latestOrder()->lineCount());
        });

        $this->step('/^(?:her|his) latest order total is "([^"]*)"$/', function ($m) {
            Assert::assertSame($m[1], $this->latestOrder()->total()->format());
        });

        $this->step('/^(?:her|his) latest order has a payment transaction reference$/', function () {
            Assert::assertNotNull($this->latestOrder()->paymentReference);
        });

        $this->step('/^(?:her|his) latest order records the deposit reference "([^"]*)"$/', function ($m) {
            Assert::assertSame($m[1], $this->latestOrder()->depositReference);
        });

        $this->step('/^(?:her|his) latest order records that the deposit was made on "([^"]*)"$/', function ($m) {
            Assert::assertSame($m[1], $this->latestOrder()->depositDate);
        });

        $this->step('/^(?:her|his) latest order\'s line for (.+?) is recorded at "([^"]*)" per unit$/', function ($m) {
            $line = $this->latestOrder()->lineFor($m[1]);
            Assert::assertNotNull($line);
            Assert::assertSame($m[2], $line->unitPrice->format());
        });

        // ---- Invoices -------------------------------------------------------

        $this->step('/^an invoice is issued for (?:her|his) order$/', function () {
            Assert::assertNotNull($this->invoices->forOrder($this->latestOrder()->reference));
        });

        $this->step('/^no invoice is issued for (?:her|his) order$/', function () {
            Assert::assertNull($this->invoices->forOrder($this->latestOrder()->reference));
        });

        $this->step('/^the invoice total is "([^"]*)"$/', function ($m) {
            $invoice = $this->invoices->forOrder($this->latestOrder()->reference);
            Assert::assertNotNull($invoice);
            Assert::assertSame($m[1], $invoice->total->format());
        });

        // ---- Notifications --------------------------------------------------

        $this->step('/^(\w+) is notified that (?:her|his) order was paid$/', function ($m) {
            Assert::assertSame(NotificationKind::OrderPaid, $this->latestNotification($m[1])->kind);
        });

        $this->step('/^(\w+) is notified that (?:her|his) order is awaiting confirmation of (?:her|his) deposit$/',
            function ($m) {
                Assert::assertSame(NotificationKind::AwaitingDepositConfirmation, $this->latestNotification($m[1])->kind);
            });

        $this->step('/^(\w+) is notified that (?:her|his) order will be paid on delivery$/', function ($m) {
            Assert::assertSame(NotificationKind::AwaitingPaymentOnDelivery, $this->latestNotification($m[1])->kind);
        });

        $this->step(
            '/^(\w+) is told at checkout that (?:her|his) card was declined$/',
            fn ($m) => $this->assertCheckoutMessageContains('card was declined'),
        );

        $this->step(
            '/^(\w+) is told at checkout that (?:her|his) bank could not be reached$/',
            fn ($m) => $this->assertCheckoutMessageContains('bank could not be reached'),
        );

        $this->step(
            '/^(\w+) is notified that (?:her|his) payment did not go through because (?:her|his) card was declined$/',
            function ($m) {
                $note = $this->latestNotification($m[1]);
                Assert::assertSame(NotificationKind::PaymentFailed, $note->kind);
                Assert::assertStringContainsString('card was declined', (string) $note->reason);
            },
        );

        $this->step(
            '/^(\w+) is notified that (?:her|his) payment did not go through because (?:her|his) bank could not be reached$/',
            function ($m) {
                $note = $this->latestNotification($m[1]);
                Assert::assertSame(NotificationKind::PaymentFailed, $note->kind);
                Assert::assertStringContainsString('bank could not be reached', (string) $note->reason);
            },
        );

        $this->step('/^the notification lists (\d+) units? of (.+)$/', function ($m) {
            $note = $this->latestNotification($this->currentUser);
            Assert::assertSame((int) $m[1], $note->quantityOf($m[2]));
        });

        $this->step('/^the notification includes the order reference$/', function () {
            $note = $this->latestNotification($this->currentUser);
            Assert::assertSame($this->latestOrder()->reference, $note->orderReference);
        });
    }

    // ---- Helpers ------------------------------------------------------------

    private function attempt(callable $action): void
    {
        $this->error = null;
        try {
            $action();
        } catch (Throwable $e) {
            $this->error = $e;
        }
    }

    private function assertRejectedBecause(string $reason): void
    {
        Assert::assertNotNull($this->error, 'Expected the action to be rejected, but it succeeded');
        $expected = strtolower(rtrim(trim($reason), '.'));
        $actual = strtolower($this->error->getMessage());
        Assert::assertStringContainsString($expected, $actual);
    }

    private function assertCheckoutMessageContains(string $needle): void
    {
        Assert::assertNotNull($this->error, 'Expected checkout to fail');
        Assert::assertStringContainsString($needle, strtolower($this->error->getMessage()));
    }

    private function latestOrder(): Order
    {
        $order = $this->orders->latestForOwner($this->currentUser);
        Assert::assertNotNull($order, "No order found for {$this->currentUser}");

        return $order;
    }

    private function latestNotification(string $name): Notification
    {
        $note = $this->notifier->lastFor($this->idOf($name));
        Assert::assertNotNull($note, "No notification found for {$name}");

        return $note;
    }

    private function idOf(string $name): string
    {
        return strtolower($name);
    }

    /** Resolve a possibly-empty captured name to a user id, defaulting to the pronoun subject. */
    private function userFrom(string $name): string
    {
        return $name === '' ? $this->currentUser : $this->idOf($name);
    }

    private function numericQuantity(string $raw): int|float|string
    {
        if (str_contains($raw, '.')) {
            return (float) $raw;
        }

        return (int) $raw;
    }
}
