<?php

namespace wmd\craftkekspay;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use craft\web\twig\variables\CraftVariable;
use wmd\craftkekspay\gateways\KeksGateway;
use wmd\craftkekspay\models\Settings;
use wmd\craftkekspay\services\KeksService;
use wmd\craftkekspay\variables\KeksVariable;
use yii\base\Event;

/**
 * KEKS Pay mobile wallet for Craft Commerce.
 *
 * @method static KeksPay getInstance()
 * @method Settings getSettings()
 * @property-read KeksService $keksService
 */
class KeksPay extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['keksService' => KeksService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        Event::on(
            Gateways::class,
            Gateways::EVENT_REGISTER_GATEWAY_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = KeksGateway::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;
                $variable->set('keks', KeksVariable::class);
            }
        );
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('keks-pay/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}
