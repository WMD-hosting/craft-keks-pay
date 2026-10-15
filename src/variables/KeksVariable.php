<?php

namespace wmd\craftkekspay\variables;

use Craft;
use craft\commerce\elements\Order;
use craft\elements\Asset;
use wmd\craftkekspay\KeksPay;

/**
 * `craft.keks` in templates.
 */
class KeksVariable
{
    /**
     * The logo asset chosen in the plugin settings, for the payment method list.
     */
    public function getLogo(): ?Asset
    {
        $ids = KeksPay::getInstance()->getSettings()->logoId;

        if (empty($ids)) {
            return null;
        }

        return Craft::$app->getAssets()->getAssetById((int)$ids[0]);
    }

    /**
     * QR code, app link and poll URL for an order, for a payment page that
     * renders its own markup: `{% set keks = craft.keks.payment(order) %}`.
     *
     * @return array{qrCode: string, appUrl: string, pollUrl: string}
     */
    public function payment(Order $order): array
    {
        return KeksPay::getInstance()->keksService->paymentDetails($order);
    }
}
