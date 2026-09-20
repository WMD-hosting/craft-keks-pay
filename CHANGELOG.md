# Release Notes for KEKS Pay

## 1.0.0 - 2026-09-20

First public release, after two years installed on Croatian stores.

- QR code and app deep link on the checkout, GET redirect into the KEKS Pay app from the Commerce payment form.
- Bank callback at `keks-pay/keks/processing` and at Commerce's webhook URL: token check, amount matched to the order, idempotent for repeated callbacks.
- Checkout page polls the order every five seconds and moves on once it is paid.
- Refund from the order's Transactions tab with the 3DES request hash.
- Settings accept environment variables; Croatian translations for the control panel and the checkout strings.
