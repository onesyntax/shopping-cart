Feature: Checkout by cash on hand
  In order to pay for my cart in person at the shop
  As a user
  We want to settle a cart by paying cash at the counter

  Paying cash at the counter settles the order immediately: the order is
  recorded as paid, an invoice is issued for it, and the cart is emptied.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Checking out an empty cart is rejected
    Given a user named Alice has an empty cart
    When Alice checks out her cart paying cash at the counter
    Then checking out is rejected because the cart is empty

  Scenario: Paying cash for a single-line cart places a paid order
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart paying cash at the counter
    Then her latest order is paid
    And her latest order's payment method is cash on hand
    And her latest order contains 2 units of Hardcover notebook
    And her latest order has 1 line
    And her latest order total is "$200.00"
    And an invoice is issued for her order
    And the invoice total is "$200.00"
    And her cart is empty
    And Alice is notified that her order was paid
    And the notification includes the order reference

  Scenario: Paying cash for a multi-line cart records every line
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And Alice has 1 unit of Ballpoint pen in her cart
    When Alice checks out her cart paying cash at the counter
    Then her latest order is paid
    And her latest order contains 2 units of Hardcover notebook
    And her latest order contains 1 unit of Ballpoint pen
    And her latest order has 2 lines
    And her latest order total is "$250.00"
    And an invoice is issued for her order
    And the invoice total is "$250.00"
    And her cart is empty
