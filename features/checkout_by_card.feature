Feature: Checkout by card
  In order to commit my cart contents as a purchase
  As a user
  We want to turn the contents of a cart into a placed order, paid by card

  Placing an order records the items, quantities, and prices that were in
  effect at the time of checkout and attempts payment through the user's card.
  Only a successful payment places an order, issues an invoice, and empties the
  cart. If payment does not go through, no order is placed and the cart is left
  untouched so the user can try again.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Checking out an empty cart is rejected
    Given a user named Alice has an empty cart
    When Alice checks out her cart paying with a valid card
    Then checking out is rejected because the cart is empty

  Scenario: Checking out a single-line cart with a valid card places a paid order
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart paying with a valid card
    Then her latest order is paid
    And her latest order contains 2 units of Hardcover notebook
    And her latest order has 1 line
    And her latest order total is "$200.00"
    And her latest order has a payment transaction reference
    And an invoice is issued for her order
    And the invoice total is "$200.00"
    And her cart is empty
    And Alice is notified that her order was paid
    And the notification lists 2 units of Hardcover notebook
    And the notification includes the order reference

  Scenario: Checking out a multi-line cart with a valid card records every line
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And Alice has 1 unit of Ballpoint pen in her cart
    When Alice checks out her cart paying with a valid card
    Then her latest order is paid
    And her latest order contains 2 units of Hardcover notebook
    And her latest order contains 1 unit of Ballpoint pen
    And her latest order has 2 lines
    And her latest order total is "$250.00"
    And an invoice is issued for her order
    And the invoice total is "$250.00"
    And her cart is empty
    And Alice is notified that her order was paid
    And the notification lists 2 units of Hardcover notebook
    And the notification lists 1 unit of Ballpoint pen
    And the notification includes the order reference

  Scenario: A declined card places no order and leaves the cart untouched
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart paying with a card that the bank declines
    Then no order is placed for her
    And her cart still contains 2 units of Hardcover notebook
    And her cart still has 1 line
    And Alice is told at checkout that her card was declined
    And Alice is notified that her payment did not go through because her card was declined

  Scenario: A gateway error places no order and leaves the cart untouched
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart paying with a card the gateway cannot reach
    Then no order is placed for her
    And her cart still contains 2 units of Hardcover notebook
    And her cart still has 1 line
    And Alice is told at checkout that her bank could not be reached
    And Alice is notified that her payment did not go through because her bank could not be reached

  Scenario: A placed order records that it was paid by card
    Given a user named Alice has 1 unit of Hardcover notebook in her cart
    When Alice checks out her cart paying with a valid card
    Then her latest order was paid by card

  Scenario: An order keeps the prices that were in effect when it was placed
    Given a user named Alice has 1 unit of Hardcover notebook in her cart
    When Alice checks out her cart paying with a valid card
    And the catalog price of Hardcover notebook is later changed to "$150.00"
    Then her latest order total is "$100.00"
    And her latest order's line for Hardcover notebook is recorded at "$100.00" per unit

  Scenario: One user's checkout does not affect another user's cart
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And a user named Bob has 1 unit of Ballpoint pen in his cart
    When Alice checks out her cart paying with a valid card
    Then Alice's cart is empty
    And Bob's cart still contains 1 unit of Ballpoint pen
    And Bob's cart still has 1 line
