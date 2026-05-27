Feature: Add item to catalog
  In order to make products available for shoppers to buy
  As a user
  We want to add items, each with a name, description and price, to the catalog

  An item's name identifies it in the catalog, so two items cannot share a name.

  Scenario: Adding a new item makes it available in the catalog
    Given a user named Alice has an empty cart
    When Alice adds the following item:
      | name               | description                                | price   |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | $100.00 |
    Then "Hardcover notebook" is available in the catalog for "$100.00"

  Scenario: Adding an item without a name is rejected
    Given a user named Alice has an empty cart
    When Alice adds the following item:
      | name | description                                | price   |
      |      | A4 hardcover notebook with 200 ruled pages | $100.00 |
    Then adding the item is rejected because the item must have a name

  Scenario: Adding an item without a description is rejected
    Given a user named Alice has an empty cart
    When Alice adds the following item:
      | name               | description | price   |
      | Hardcover notebook |             | $100.00 |
    Then adding the item is rejected because the item must have a description

  Scenario: Adding an item priced at zero is rejected
    Given a user named Alice has an empty cart
    When Alice adds the following item:
      | name               | description                                | price |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | $0.00 |
    Then adding the item is rejected because the price must be greater than zero

  Scenario: Adding an item with a negative price is rejected
    Given a user named Alice has an empty cart
    When Alice adds the following item:
      | name               | description                                | price   |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | -$10.00 |
    Then adding the item is rejected because the price must be greater than zero

  Scenario: Adding an item whose name is already in the catalog is rejected
    Given a user named Alice has an empty cart
    And the catalog already contains an item named "Hardcover notebook"
    When Alice adds the following item:
      | name               | description                         | price   |
      | Hardcover notebook | A second hardcover notebook listing | $120.00 |
    Then adding the item is rejected because an item named "Hardcover notebook" already exists
