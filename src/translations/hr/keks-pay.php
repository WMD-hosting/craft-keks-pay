<?php

/**
 * Croatian control-panel and checkout strings.
 */
return [
    'KEKS Pay' => 'KEKS Pay',
    'Scan the code with the KEKS Pay app.' => 'Skenirajte kod KEKS Pay aplikacijom.',
    'KEKS Pay QR code' => 'KEKS Pay QR kod',
    'Open the KEKS Pay app' => 'Otvori KEKS Pay aplikaciju',
    'Waiting for the payment confirmation…' => 'Čekamo potvrdu plaćanja…',
    'Merchant' => 'Trgovac',
    'CID' => 'CID',
    'Customer ID issued by KEKS Pay.' => 'Customer ID koji izdaje KEKS Pay.',
    'TID' => 'TID',
    'Terminal ID issued by KEKS Pay.' => 'Terminal ID koji izdaje KEKS Pay.',
    'Key' => 'Ključ',
    '3DES key issued by KEKS Pay, used to sign refunds. Prefer an environment variable, e.g. $KEKS_KEY.' => '3DES ključ koji izdaje KEKS Pay, potpisuje povrate. Preporuka je varijabla okoline, npr. $KEKS_KEY.',
    'Callback token' => 'Token za callback',
    'Token the bank sends on the payment callback as "Authorization: Token …". Leave empty if KEKS Pay did not set one up for you.' => 'Token koji banka šalje na callback kao "Authorization: Token …". Prazno ako ga KEKS Pay nije postavio.',
    'Endpoints' => 'Adrese',
    'App URL' => 'URL aplikacije',
    'Base of the payment deep link, normally https://kekspay.hr/.' => 'Osnova deep linka za plaćanje, u pravilu https://kekspay.hr/.',
    'API URL' => 'URL API-ja',
    'e-retailer API base. https://kekspayuat.erstebank.hr/ for testing; KEKS Pay gives you the production host.' => 'Osnova e-retailer API-ja. https://kekspayuat.erstebank.hr/ za testiranje; produkcijski host daje KEKS Pay.',
    'Storefront' => 'Web trgovina',
    'Logo' => 'Logo',
    'Shown next to the payment method; available in templates as craft.keks.logo.' => 'Prikazuje se uz način plaćanja; u predlošcima dostupan kao craft.keks.logo.',
    'Payment page' => 'Stranica plaćanja',
    'Site page the customer is sent to after placing the order, showing the QR code and the app link, e.g. `checkout/keks-pay?number={number}`. Leave empty to send the customer straight into the app (phones only).' => 'Stranica na koju kupac dolazi nakon narudžbe, s QR kodom i poveznicom na aplikaciju, npr. `checkout/keks-pay?number={number}`. Prazno šalje kupca ravno u aplikaciju (samo za mobitele).',
    'Fallback success URL' => 'Rezervni URL uspjeha',
    'Used when the order has no return URL.' => 'Koristi se kad narudžba nema return URL.',
    'Fallback cancel URL' => 'Rezervni URL otkaza',
    'Used when the order has no cancel URL.' => 'Koristi se kad narudžba nema cancel URL.',
    'Callback URL' => 'Callback URL',
    'Give KEKS Pay this URL for the payment result callback:' => 'Ovaj URL dajte KEKS Payu za callback rezultata plaćanja:',
];
