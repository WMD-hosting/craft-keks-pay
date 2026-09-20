<?php

namespace wmd\craftkekspay\variables;

use Craft;
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
}
