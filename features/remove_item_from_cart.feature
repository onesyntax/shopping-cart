Feature: Remove item from cart
  In order to amend my cart before checking out
  As a user
  We want to remove items from a cart

  Removing an item takes its entire line off the cart, no matter how
  many units of that item the line holds.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Removing an item that is not in the catalog is rejected
    Given a user named Alice has an empty cart
    When Alice removes Vintage typewriter from her cart
    Then removing the item is rejected because the item is not in the catalog

  Scenario: Removing an item that is not in her cart is rejected
    Given a user named Alice has an empty cart
    When Alice removes Hardcover notebook from her cart
    Then removing the item is rejected because the item is not in the cart

  Scenario: Removing the only item in a cart leaves it empty
    Given a user named Alice has 1 unit of Hardcover notebook in her cart
    When Alice removes Hardcover notebook from her cart
    Then her cart is empty
    And her cart total is "$0.00"

  Scenario: Removing an item with multiple units takes the whole line off the cart
    Given a user named Alice has 3 units of Hardcover notebook in her cart
    When Alice removes Hardcover notebook from her cart
    Then her cart is empty
    And her cart total is "$0.00"

  Scenario: Removing one item from a multi-line cart leaves the other lines intact
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And Alice has 1 unit of Ballpoint pen in her cart
    When Alice removes Hardcover notebook from her cart
    Then her cart contains 1 unit of Ballpoint pen
    And her cart has 1 line
    And her cart total is "$50.00"

  Scenario: One user removing an item does not affect another user's cart
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And a user named Bob has 2 units of Hardcover notebook in his cart
    When Alice removes Hardcover notebook from her cart
    Then Alice's cart is empty
    And Bob's cart still contains 2 units of Hardcover notebook
    And Bob's cart still has 1 line
