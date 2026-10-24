# Today Sell

A small shop for browsing active products and placing a cash-on-delivery order. The customer form asks only for a product, customer name, mobile number, and address. Prices, delivery, stock, and the order total are calculated on the server.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Copy `.env.example` to `.env` if you do not already have one.
3. Create the database and demo records:

```bash
php database/migrate.php --fresh
php database/seeds/seed.php
```

4. Open [http://localhost/today-sell/public](http://localhost/today-sell/public).

Demo password for every account is `password`.

| Account | Email |
| --- | --- |
| Admin | admin@todaysell.test |
| Manager | manager@todaysell.test |
| Staff | staff@todaysell.test |
| Customer | customer@todaysell.test |

## Customer order

From the home page or Place order, choose a product and enter the customer name, mobile number, and address. Guests do not need an account. Registered customers can use the same form. Cash on delivery is the default and stays unpaid until a staff member records the collection. bKash and Nagad can be selected instead; those payments stay unverified until an authorized staff member checks the transaction. Email, city, and country are optional. Country defaults to Bangladesh.

Delivery is ৳60 inside Dhaka, ৳120 outside Dhaka, and free when the product subtotal is at least ৳1500. An optional coupon can be added, but invalid, expired, and overused coupons are rejected.

## Staff

Admin and manager accounts can manage products, coupons, inventory, reports, and payment verification. Staff can view and update orders, record cash collection, and submit bKash or Nagad payments. A different manager or admin must verify a mobile payment. Staff cannot open user management, backups, reports, products, or coupons.

Invoices store the product name and price from the moment the order is placed. Later catalog edits do not change them.

## Release choices

- Currency is BDT. International orders are charged in BDT.
- bKash and Nagad are checked manually. A different authorized user must verify a submission.
- Delivery fees are flat rates by zone: Dhaka ৳60, the rest of Bangladesh ৳120, international ৳900. Delivery is free from ৳1,500 inside Bangladesh. Cash on delivery is available only in Bangladesh.
- Products are single SKUs. Stock is reduced when the order is placed and restored on an eligible cancellation or an approved return of a delivered order.
- A delivered order cannot be cancelled. It uses the return action.
- Password reset links expire after one hour and work once. This server shows the link on the reset page because email delivery is not configured.

## Checks

```bash
php tests/run.php
```

The test script uses the separate `today_sell_test` database and covers product browsing, guest and registered orders, server totals, stock races, cash collection, bKash/Nagad verification, coupons, cancellation and returns, staff permissions, invoice history, report reconciliation, backup restoration, and basic request security.
