<?php

namespace wmd\craftkekspay\services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\GuzzleException;
use wmd\craftkekspay\KeksPay;
use yii\base\Component;

/**
 * Payment payloads, the refund API and the request hash of the KEKS Pay
 * e-retailer integration.
 */
class KeksService extends Component
{
    private string $cid = '';
    private string $tid = '';
    private string $key = '';
    private string $appUrl = '';
    private string $apiUrl = '';

    public function init(): void
    {
        parent::init();

        $settings = KeksPay::getInstance()->getSettings();
        $this->cid = (string)App::parseEnv($settings->cid);
        $this->tid = (string)App::parseEnv($settings->tid);
        $this->key = (string)App::parseEnv($settings->key);
        $this->appUrl = rtrim((string)App::parseEnv($settings->storeUrl), '/');
        $this->apiUrl = rtrim((string)App::parseEnv($settings->storeUrlApi), '/');
    }

    /**
     * The payload the KEKS Pay app reads, as a QR code or as query parameters.
     */
    public function paymentData(Transaction $transaction): array
    {
        return [
            'qr_type' => 1,
            'cid' => $this->cid,
            'tid' => $this->tid,
            'bill_id' => (string)$transaction->orderId,
            'amount' => number_format((float)$transaction->paymentAmount, 2, '.', ''),
            'currency' => $transaction->paymentCurrency,
        ];
    }

    /**
     * Deep link that opens the KEKS Pay app (or its web fallback) with the payment.
     */
    public function paymentUrl(Transaction $transaction): string
    {
        $order = $transaction->getOrder();
        $settings = KeksPay::getInstance()->getSettings();
        $success = (string)$order->returnUrl ?: (string)App::parseEnv($settings->successUrl);
        $fail = (string)$order->cancelUrl ?: (string)App::parseEnv($settings->failUrl);

        // The app opens these outside the site, so a relative return URL
        // (`checkout/complete?number=…`, as Commerce stores it) must be made absolute.
        return $this->appUrl . '/galebpay?' . http_build_query($this->paymentData($transaction) + [
            'success_url' => UrlHelper::siteUrl($success),
            'fail_url' => UrlHelper::siteUrl($fail),
        ]);
    }

    /**
     * Where "Pay" sends the customer: the site's payment page when one is
     * configured (it shows the QR code on desktop and the app link on phones),
     * otherwise straight into the app.
     */
    public function checkoutUrl(Transaction $transaction): string
    {
        $page = trim((string)App::parseEnv(KeksPay::getInstance()->getSettings()->paymentPageUrl));
        if ($page === '') {
            return $this->paymentUrl($transaction);
        }

        $order = $transaction->getOrder();

        return UrlHelper::siteUrl(Craft::$app->getView()->renderSandboxedObjectTemplate($page, $order, $order->getObjectTemplateVariables()));
    }

    /**
     * Everything a payment page needs for an order: the QR code, the app link
     * and the URL that reports whether the order has been paid.
     *
     * @return array{qrCode: string, appUrl: string, pollUrl: string}
     */
    public function paymentDetails(Order $order): array
    {
        $transaction = Commerce::getInstance()->getTransactions()->createTransaction($order);

        return [
            'qrCode' => $this->qrCode($transaction),
            'appUrl' => $this->paymentUrl($transaction),
            'pollUrl' => UrlHelper::actionUrl('keks-pay/keks/check-transaction', ['number' => $order->number]),
        ];
    }

    /**
     * QR code of the payment payload as an SVG data URI, for desktop checkouts.
     */
    public function qrCode(Transaction $transaction): string
    {
        $options = new QROptions([
            'version' => 6,
            'quietzoneSize' => 4,
            'eccLevel' => QRCode::ECC_L,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render(json_encode($this->paymentData($transaction)));
    }

    /**
     * Refund of a paid order through the e-retailer API.
     */
    public function refund(Order $order, float $amount, string $currency): array
    {
        $timestamp = time();
        $amountString = number_format($amount, 2, '.', '');
        $payload = [
            'bill_id' => (string)$order->id,
            'tid' => $this->tid,
            'cid' => $this->cid,
            'amount' => $amountString,
            'epochtime' => $timestamp,
            'hash' => $this->hash($timestamp, $amountString, (string)$order->id),
            'currency' => $currency,
        ];

        try {
            $response = Craft::createGuzzleClient(['timeout' => 20, 'connect_timeout' => 10])->post($this->apiUrl . '/eretailer/keksrefund', [
                'json' => $payload,
                'headers' => ['Accept' => 'application/json; charset=utf-8'],
            ]);
            $decoded = json_decode((string)$response->getBody(), true);
        } catch (GuzzleException $e) {
            Craft::error('KEKS Pay refund request failed: ' . $e->getMessage(), __METHOD__);
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Request hash as the KEKS Pay manual defines it: MD5 of
     * epochtime + tid + amount + bill_id, 3DES-CBC encrypted with the key,
     * zero IV, upper-case hex.
     */
    public function hash(int $timestamp, string $amount, string $billId): string
    {
        $checksum = pack('H*', md5($timestamp . $this->tid . $amount . $billId));
        $iv = str_repeat(pack('c', 0), 8);

        return strtoupper(bin2hex(openssl_encrypt($checksum, 'des-ede3-cbc', $this->key, OPENSSL_RAW_DATA, $iv)));
    }
}
