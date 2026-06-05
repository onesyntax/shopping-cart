<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Http;

use App\Catalog\UseCases\AddItemToCatalog;
use App\Catalog\UseCases\Inputs\AddItemToCatalogInput;
use App\Catalog\UseCases\ListCatalog;
use App\Foundation\Domain\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The storefront: browse the catalog and list new items into it. Thin — each
 * action delegates to a single use case and translates any domain-rule
 * violation into a flash message.
 */
final class CatalogController
{
    public function index(ListCatalog $listCatalog): View
    {
        return view('catalog.index', ['items' => $listCatalog->handle()]);
    }

    public function create(): View
    {
        return view('catalog.create');
    }

    public function store(AddItemToCatalogRequest $request, AddItemToCatalog $addItem): RedirectResponse
    {
        try {
            $addItem->handle(new AddItemToCatalogInput(
                $request->string('name')->toString(),
                $request->string('description')->toString(),
                $request->string('price')->toString(),
            ));
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect('/')->with('status', $request->string('name')->toString().' is now in the catalogue.');
    }
}
