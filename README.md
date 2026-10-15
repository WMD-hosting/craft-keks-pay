# KEKS Pay for Craft Commerce

[KEKS Pay](https://kekspay.hr/) mobile wallet as a Craft Commerce gateway:
the customer pays in the KEKS Pay app, reached through a deep link on the
phone or by scanning the QR code on the checkout page, the bank calls back
with the result, refunds from the control panel, Croatian translations.

## Requirements

Craft CMS 5.0+, Craft Commerce 5.0+, PHP 8.2+ with OpenSSL, and a KEKS Pay
e-retailer account (CID, TID and key from KEKS Pay, UAT access for testing).

## Installation

```sh
composer require wmd/craft-keks-pay
php craft plugin/install keks-pay
```

Then **Commerce → System Settings → Gateways → New gateway**, type
**KEKS Pay**.

## Settings

**Settings → Plugins → KEKS Pay**

| Setting | Notes |
|---|---|
| CID, TID, Key | issued by KEKS Pay; environment variables are supported |
| Callback token | the bank sends it as `Authorization: Token …`; empty disables the check |
| App URL | `https://kekspay.hr/` |
| API URL | `https://kekspayuat.erstebank.hr/` while testing; KEKS Pay gives you the production host |
| Logo | shown next to the payment method, `craft.keks.logo` in templates |
| Payment page | site page "Pay" leads to, e.g. `checkout/keks-pay?number={number}`; empty sends the customer straight into the app |
| Fallback success / cancel URL | used when the order carries no return or cancel URL |

Give KEKS Pay the callback URL the settings page prints:

```
https://example.com/actions/keks-pay/keks/processing
```

Commerce's generic webhook URL (`commerce/webhooks/process-webhook?gateway=ID`)
works as well.

## How a payment runs

1. The customer places the order through Commerce's payment form
   (`commerce/payments/pay`). With a **Payment page** set, the gateway
   redirects there; without one, straight into the app with the order's
   return and cancel URLs (which only works on a phone).
2. The payment page shows the QR code (desktop) and an "Open the KEKS Pay
   app" link (phones). Either render the plugin's fragment with
   `gateway.getPaymentFormHtml({ order })`, or build your own markup from
   `craft.keks.payment(order)`:

   ```twig
   {% set order = craft.orders.number(craft.app.request.getParam('number')).one() %}
   {% set keks = craft.keks.payment(order) %}
   <img src="{{ keks.qrCode }}" alt="KEKS Pay QR code">
   <a href="{{ keks.appUrl }}">Open the KEKS Pay app</a>
   {# poll keks.pollUrl, see below #}
   ```

3. The customer confirms in the app. The bank calls the callback with
   `status`, `message`, `bill_id` and `amount`; the plugin checks the token,
   matches the amount to the order, records a purchase transaction and marks
   the order paid, which completes it. Repeated callbacks for a paid order are
   acknowledged without duplicating the transaction.
4. The payment page polls `pollUrl` (`keks-pay/keks/check-transaction?number=…`).
   It answers `{"result": true, "url": …}` once the order is paid (go to the
   order's return URL), `{"failed": true, "url": …}` when the bank reported a
   failed payment (the order's cancel URL), and `{"result": false}` while it
   waits.
5. Refund from the order's Transactions tab, for the full amount, signed with
   the 3DES request hash the KEKS Pay manual defines.

## Testing

Use the UAT API host with the test CID, TID and key KEKS Pay provides, and the
KEKS Pay test app.

## License

MIT. Developed by [WMD](https://wmd.hr). KEKS Pay is a trademark of Erste &
Steiermärkische Bank d.d.
