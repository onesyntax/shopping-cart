<?php

declare(strict_types=1);

namespace App\Cart\UseCases;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartRepository;
use App\Cart\UseCases\Inputs\RemoveItemFromCartInput;
use App\Catalog\Domain\ItemNotInCatalog;
use App\Catalog\Domain\ItemRepository;

/**
 * Removes an item's entire line from a shopper's cart. The item must exist in
 * the catalog and be present on the cart.
 */
final class RemoveItemFromCart
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly CartRepository $carts,
    ) {}

    public function handle(RemoveItemFromCartInput $input): Cart
    {
        if (! $this->items->existsByName($input->itemName)) {
            throw ItemNotInCatalog::named($input->itemName);
        }

        $cart = $this->carts->forOwner($input->ownerId);
        $cart->removeItem($input->itemName);
        $this->carts->save($cart);

        return $cart;
    }
}
