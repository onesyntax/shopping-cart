Feature: Checkout by cash on delivery
  In order to receive my cart's contents and pay for them when they arrive
  As a user
  We want to place an order to be paid in cash on delivery

  Choosing cash on delivery places the order to be sent out and issues an
  invoice, but leaves the order awaiting payment until the cash is collected on
  delivery. The cart is emptied. Collecting the cash at the doorstep is handled
  separately, later.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Checking out an empty cart is rejected
    Given a user named Alice has an empty cart
    When Alice checks out her cart to pay cash on delivery
    Then checking out is rejected because the cart is empty

  Scenario: Choosing cash on delivery places an order ready to be sent out
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart to pay cash on delivery
    Then her latest order is awaiting payment on delivery
    And her latest order's payment method is cash on delivery
    And her latest order contains 2 units of Hardcover notebook
    And her latest order has 1 line
    And her latest order total is "$200.00"
    And an invoice is issued for her order
    And the invoice total is "$200.00"
    And her cart is empty
    And Alice is notified that her order will be paid on delivery
    And the notification includes the order reference

  Scenario: A multi-line cash-on-delivery order records every line
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And Alice has 1 unit of Ballpoint pen in her cart
    When Alice checks out her cart to pay cash on delivery
    Then her latest order is awaiting payment on delivery
    And her latest order contains 2 units of Hardcover notebook
    And her latest order contains 1 unit of Ballpoint pen
    And her latest order has 2 lines
    And her latest order total is "$250.00"
    And an invoice is issued for her order
    And the invoice total is "$250.00"
    And her cart is empty
