<?php

namespace wmd\craftkekspay\models;

use craft\base\Model;

/**
 * Plugin settings. Secrets accept environment variables (`$KEKS_KEY`).
 */
class Settings extends Model
{
    /** @var string Customer (merchant) ID issued by KEKS Pay. */
    public string $cid = '';

    /** @var string Terminal ID issued by KEKS Pay. */
    public string $tid = '';

    /** @var string 3DES key issued by KEKS Pay, used for the refund hash. */
    public string $key = '';

    /** @var string Token the bank sends as `Authorization: Token …` on the callback; empty disables the check. */
    public string $authToken = '';

    /** @var string KEKS Pay app base URL for the payment deep link. */
    public string $storeUrl = 'https://kekspay.hr/';

    /** @var string e-retailer API base URL: the UAT host for testing, the production host KEKS Pay gives you. */
    public string $storeUrlApi = 'https://kekspayuat.erstebank.hr/';

    /** @var string Where the customer lands after a successful payment when the order has no return URL. */
    public string $successUrl = '';

    /** @var string Where the customer lands after a failed payment when the order has no cancel URL. */
    public string $failUrl = '';

    /** @var array Asset ID of the logo shown next to the payment method. */
    public array $logoId = [];

    public function rules(): array
    {
        return [
            [['cid', 'tid', 'key', 'storeUrl', 'storeUrlApi'], 'required'],
        ];
    }
}
