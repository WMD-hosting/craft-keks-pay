<?php

namespace wmd\craftkekspay\gateways;

use Craft;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\OffsitePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\helpers\App;
use craft\web\Response as WebResponse;
use craft\web\View;
use wmd\craftkekspay\KeksPay;
use wmd\craftkekspay\responses\CheckoutResponse;
use wmd\craftkekspay\responses\RefundResponse;
use yii\base\NotSupportedException;

/**
 * KEKS Pay: the customer pays in the KEKS Pay app, reached through a deep
 * link (mobile) or by scanning a QR code (desktop) on the payment page that
 * "Pay" leads to. The bank then calls back with the result, and the payment
 * page polls the order until it is paid.
 */
class KeksGateway extends BaseGateway
{
    public static function displayName(): string
    {
        return Craft::t('keks-pay', 'KEKS Pay');
    }

    /**
     * The QR code and the app link for the checkout's payment form.
     */
    public function getPaymentFormHtml(array $params): ?string
    {
        // Without an order there is no transaction to show a code for or to poll. A checkout that
        // lists every gateway's form up front calls this with no params; the code comes after "Pay".
        $order = $params['order'] ?? null;
        if (!$order instanceof Order) {
            return null;
        }

        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $params += KeksPay::getInstance()->keksService->paymentDetails($order);

        $html = $view->renderTemplate('keks-pay/form.twig', $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    public function getPaymentFormModel(): BasePaymentForm
    {
        return new OffsitePaymentForm();
    }

    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return new CheckoutResponse($transaction);
    }

    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        return new CheckoutResponse($transaction, CheckoutResponse::STATUS_SUCCESSFUL);
    }

    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Authorizing is not supported by this gateway'));
    }

    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Capturing is not supported by this gateway'));
    }

    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        throw new NotSupportedException(Craft::t('commerce', 'Authorizing is not supported by this gateway'));
    }

    public function refund(Transaction $transaction): RequestResponseInterface
    {
        $result = KeksPay::getInstance()->keksService->refund($transaction->getOrder(), (float)$transaction->amount, $transaction->currency);

        return new RefundResponse($result);
    }

    public function createPaymentSource(BasePaymentForm $sourceData, int $customerId): PaymentSource
    {
        throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
    }

    public function deletePaymentSource(string $token): bool
    {
        throw new NotSupportedException(Craft::t('commerce', 'Payment sources are not supported by this gateway'));
    }

    /**
     * The bank's payment callback, reachable at Commerce's webhook URL
     * (`commerce/webhooks/process-webhook?gateway=ID`) and at the plugin's own
     * `keks-pay/keks/processing` action.
     */
    public function processWebHook(): WebResponse
    {
        $request = Craft::$app->getRequest();
        $response = Craft::$app->getResponse();
        $response->format = WebResponse::FORMAT_JSON;

        $settings = KeksPay::getInstance()->getSettings();
        $token = (string)App::parseEnv($settings->authToken);
        if ($token !== '' && !hash_equals("Token {$token}", (string)$request->getHeaders()->get('Authorization'))) {
            $response->setStatusCode(403);
            $response->data = ['status' => 403, 'message' => 'The Authorization token is missing or incorrect.'];
            return $response;
        }

        $body = json_decode((string)$request->getRawBody(), true);
        if (!is_array($body) || !isset($body['status'], $body['message'], $body['bill_id'], $body['amount'])) {
            $response->setStatusCode(400);
            $response->data = ['status' => 400, 'message' => 'Required parameters are missing.'];
            return $response;
        }

        $order = ctype_digit((string)$body['bill_id']) ? Order::find()->id((int)$body['bill_id'])->one() : null;
        if (!$order) {
            $response->setStatusCode(404);
            $response->data = ['status' => 404, 'message' => 'Order not found.'];
            return $response;
        }

        $paid = (int)$body['status'] === 0 && strtolower((string)$body['message']) === 'paid';

        if ($paid && abs((float)$body['amount'] - (float)$order->getTotalPrice()) > 0.005) {
            Craft::warning("KEKS Pay reported {$body['amount']} for order {$order->id}, which totals {$order->getTotalPrice()}.", __METHOD__);
            $response->setStatusCode(400);
            $response->data = ['status' => 400, 'message' => 'Amount does not match the order.'];
            return $response;
        }

        if ($paid && $order->getIsPaid()) {
            $response->data = ['status' => 0, 'message' => 'Accepted'];
            return $response;
        }

        $this->record($order, $body, $paid ? TransactionRecord::STATUS_SUCCESS : TransactionRecord::STATUS_FAILED);

        $response->data = $paid
            ? ['status' => 0, 'message' => 'Accepted']
            : ['status' => (int)$body['status'], 'message' => (string)$body['message']];

        return $response;
    }

    /**
     * Record the callback as a purchase transaction on the order, as a child
     * of the pending transaction the checkout opened when one exists.
     */
    private function record(Order $order, array $body, string $status): void
    {
        $transactions = Commerce::getInstance()->getTransactions();
        $parent = $order->getLastTransaction();
        if ($parent && ($parent->gatewayId !== $this->id || $parent->status !== TransactionRecord::STATUS_PENDING)) {
            $parent = null;
        }

        $transaction = $transactions->createTransaction($order, $parent);
        $transaction->gatewayId = $this->id;
        $transaction->type = TransactionRecord::TYPE_PURCHASE;
        $transaction->status = $status;
        $transaction->response = $body;
        $transaction->reference = (string)($body['keks_id'] ?? $body['bill_id']);
        $transaction->message = (string)$body['message'];
        $transaction->amount = (float)$body['amount'];
        $transaction->paymentAmount = (float)$body['amount'];
        $transaction->currency = $order->currency;
        $transaction->paymentCurrency = $order->paymentCurrency ?: $order->currency;

        $transactions->saveTransaction($transaction);
        $order->updateOrderPaidInformation();
    }

    public function supportsAuthorize(): bool
    {
        return false;
    }

    public function supportsCapture(): bool
    {
        return false;
    }

    public function supportsCompleteAuthorize(): bool
    {
        return false;
    }

    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    public function supportsPaymentSources(): bool
    {
        return false;
    }

    public function supportsPurchase(): bool
    {
        return true;
    }

    public function supportsRefund(): bool
    {
        return true;
    }

    public function supportsPartialRefund(): bool
    {
        return false;
    }

    public function supportsPartialPayment(): bool
    {
        return false;
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }
}
