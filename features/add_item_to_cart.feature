Feature: Add item to cart
  In order to assemble a purchase before checking out
  As a user
  We want to add items, in any quantity, to a cart

  Adding the same item to a cart that already contains it increases the
  quantity on the existing line rather than creating a duplicate line.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Adding an item that is not in the catalog is rejected
    Given a user named Alice has an empty cart
    When Alice adds 1 unit of Vintage typewriter to her cart
    Then adding the item is rejected because the item is not in the catalog

  Scenario: Adding zero units of an item is rejected
    Given a user named Alice has an empty cart
    When Alice adds 0 units of Hardcover notebook to her cart
    Then adding the item is rejected because the quantity must be at least 1

  Scenario: Adding a negative number of units is rejected
    Given a user named Alice has an empty cart
    When Alice adds -1 units of Hardcover notebook to her cart
    Then adding the item is rejected because the quantity must be at least 1

  Scenario: Adding a fractional number of units is rejected
    Given a user named Alice has an empty cart
    When Alice adds 1.5 units of Hardcover notebook to her cart
    Then adding the item is rejected because the quantity must be a whole number

  Scenario: Adding a single unit of an item to an empty cart
    Given a user named Alice has an empty cart
    When Alice adds 1 unit of Hardcover notebook to her cart
    Then her cart contains 1 unit of Hardcover notebook
    And her cart total is "$100.00"

  Scenario: Adding several units of an item in one go
    Given a user named Alice has an empty cart
    When Alice adds 3 units of Hardcover notebook to her cart
    Then her cart contains 3 units of Hardcover notebook
    And her cart total is "$300.00"

  Scenario: Adding more of an item already in the cart merges into the existing line
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice adds 4 more units of Hardcover notebook to her cart
    Then her cart contains 6 units of Hardcover notebook
    And her cart has 1 line
    And her cart total is "$600.00"

  Scenario: Adding a different item creates a new line on the cart
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice adds 1 unit of Ballpoint pen to her cart
    Then her cart contains 2 units of Hardcover notebook
    And her cart contains 1 unit of Ballpoint pen
    And her cart has 2 lines
    And her cart total is "$250.00"

  Scenario: One user's items do not appear in another user's cart
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And a user named Bob has an empty cart
    When Bob adds 1 unit of Ballpoint pen to his cart
    Then Bob's cart contains 1 unit of Ballpoint pen
    And Bob's cart has 1 line
    And Alice's cart still contains 2 units of Hardcover notebook
    And Alice's cart still has 1 line
