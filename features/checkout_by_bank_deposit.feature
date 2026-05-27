Feature: Checkout by bank deposit
  In order to pay for my cart by transferring money to the shop's bank
  As a user
  We want to place an order against a bank deposit we have already made

  Choosing bank deposit records the deposit's reference and the date it was
  made, and leaves the order awaiting confirmation until the deposit is
  verified against the bank. No invoice is issued until the order is confirmed.
  The cart is emptied. Verifying the deposit with the bank is handled
  separately, later.

  Background:
    Given the following items are available:
      | name               | description                                | price (cents) |
      | Hardcover notebook | A4 hardcover notebook with 200 ruled pages | 10000         |
      | Ballpoint pen      | Blue ink ballpoint pen, pack of 5          | 5000          |

  Scenario: Checking out an empty cart is rejected
    Given a user named Alice has an empty cart
    When Alice checks out her cart paying by bank deposit with reference "BD-48217" made on "2026-06-01"
    Then checking out is rejected because the cart is empty

  Scenario: Placing an order against a bank deposit leaves it awaiting confirmation
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    When Alice checks out her cart paying by bank deposit with reference "BD-48217" made on "2026-06-01"
    Then her latest order is awaiting payment confirmation
    And her latest order's payment method is bank deposit
    And her latest order records the deposit reference "BD-48217"
    And her latest order records that the deposit was made on "2026-06-01"
    And her latest order contains 2 units of Hardcover notebook
    And her latest order has 1 line
    And her latest order total is "$200.00"
    And no invoice is issued for her order
    And her cart is empty
    And Alice is notified that her order is awaiting confirmation of her deposit
    And the notification includes the order reference

  Scenario: A multi-line bank-deposit order records every line and stays awaiting confirmation
    Given a user named Alice has 2 units of Hardcover notebook in her cart
    And Alice has 1 unit of Ballpoint pen in her cart
    When Alice checks out her cart paying by bank deposit with reference "BD-90553" made on "2026-06-02"
    Then her latest order is awaiting payment confirmation
    And her latest order contains 2 units of Hardcover notebook
    And her latest order contains 1 unit of Ballpoint pen
    And her latest order has 2 lines
    And her latest order total is "$250.00"
    And no invoice is issued for her order
    And her cart is empty
