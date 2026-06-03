<?php

declare(strict_types=1);

namespace Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Tests\Acceptance\ShoppingCartContext;

/**
 * Behat entry point for the feature suite. Behat builds a fresh context per
 * scenario, so each scenario gets a clean in-memory world. Rather than spell
 * out one method per step, this context delegates every step to the shared
 * regex-based engine (Tests\Acceptance\ShoppingCartContext), which is also what
 * the Pest acceptance runner uses — one set of step definitions, two runners.
 */
final class FeatureContext implements Context
{
    private ShoppingCartContext $engine;

    public function __construct()
    {
        $this->engine = new ShoppingCartContext;
    }

    // Behat step definitions are not keyword-bound: this one regex matches
    // every Given/When/Then/And step and routes it to the shared engine.
    #[Given('/^(.*)$/')]
    public function dispatch(string $text, ?TableNode $table = null): void
    {
        $this->engine->run($text, $table);
    }
}
