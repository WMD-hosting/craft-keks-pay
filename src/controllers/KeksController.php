<?php

namespace wmd\craftkekspay\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\web\Controller;
use wmd\craftkekspay\gateways\KeksGateway;
use yii\web\Response;

/**
 * Bank callback and the checkout page's payment poll.
 *
 * Callback URL to give KEKS Pay: actions/keks-pay/keks/processing
 */
class KeksController extends Controller
{
    protected array|int|bool $allowAnonymous = true;
    public $enableCsrfValidation = false;

    /**
     * The bank reports the payment result here. Same handling as Commerce's
     * generic webhook URL; this one needs no gateway id in the query string.
     */
    public function actionProcessing(): Response
    {
        $this->requirePostRequest();

        foreach (Commerce::getInstance()->getGateways()->getAllGateways() as $gateway) {
            if ($gateway instanceof KeksGateway) {
                return $gateway->processWebHook();
            }
        }

        $response = Craft::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->setStatusCode(500);
        $response->data = ['status' => 500, 'message' => 'No KEKS Pay gateway is configured.'];

        return $response;
    }

    /**
     * Polled by the checkout page until the order is paid.
     */
    public function actionCheckTransaction(): Response
    {
        $id = (string)Craft::$app->getRequest()->getParam('orderId', '');
        $order = ctype_digit($id) ? Order::find()->id((int)$id)->one() : null;

        if (!$order) {
            return $this->asJson(['stop' => true]);
        }

        if ($order->getIsPaid()) {
            return $this->asJson(['result' => true, 'url' => (string)$order->returnUrl ?: '/']);
        }

        return $this->asJson(['result' => false]);
    }
}
