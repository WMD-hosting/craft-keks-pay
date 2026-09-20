<?php

namespace wmd\craftkekspay\responses;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\Transaction;
use wmd\craftkekspay\KeksPay;

/**
 * On purchase: a GET redirect into the KEKS Pay app deep link. On completion:
 * success (the bank callback has already recorded the payment).
 */
class CheckoutResponse implements RequestResponseInterface
{
    public const STATUS_REDIRECT = 'redirect';
    public const STATUS_SUCCESSFUL = 'successful';

    public function __construct(private Transaction $transaction, private string $status = self::STATUS_REDIRECT)
    {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return $this->status === self::STATUS_REDIRECT;
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
        return KeksPay::getInstance()->keksService->paymentUrl($this->transaction);
    }

    public function getTransactionReference(): string
    {
        return (string)$this->transaction->hash;
    }

    public function getCode(): string
    {
        return '';
    }

    public function getData(): array
    {
        return KeksPay::getInstance()->keksService->paymentData($this->transaction);
    }

    public function getMessage(): string
    {
        return '';
    }

    public function redirect(): void
    {
        Craft::$app->getResponse()->redirect($this->getRedirectUrl())->send();
        Craft::$app->end();
    }
}
