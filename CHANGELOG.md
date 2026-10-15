# Release Notes for KEKS Pay

## 1.1.0 - 2026-10-15

- New **Payment page** setting. When set (e.g. `checkout/keks-pay?number={number}`), "Pay" sends the customer to that site page instead of straight into the app, so desktop customers get a QR code to scan. Empty keeps the old redirect into the app.
- `craft.keks.payment(order)` returns the QR code, the app link and the poll URL, for a payment page that renders its own markup.
- The payment poll looks orders up by number instead of id. Ids are sequential, and a paid order's poll answer leads to its confirmation page.
- The poll reports a failed payment (`failed: true` and the order's cancel URL) instead of waiting forever.
- The app's success and fail URLs are absolute. Commerce stores the order's return URL relative to the site, which the app could not open.

## 1.0.1 - 2026-10-15

- `getPaymentFormHtml()` returns nothing when no order is passed, instead of a Twig error. A checkout that renders every gateway's form before payment (`getPaymentFormHtml({})`) no longer fails with a 500.

## 1.0.0 - 2026-09-20

First public release, after two years installed on Croatian stores.

- QR code and app deep link on the checkout, GET redirect into the KEKS Pay app from the Commerce payment form.
- Bank callback at `keks-pay/keks/processing` and at Commerce's webhook URL: token check, amount matched to the order, idempotent for repeated callbacks.
- Checkout page polls the order every five seconds and moves on once it is paid.
- Refund from the order's Transactions tab with the 3DES request hash.
- Settings accept environment variables; Croatian translations for the control panel and the checkout strings.
