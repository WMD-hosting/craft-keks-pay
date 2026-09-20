<?php

namespace wmd\craftkekspay\responses;

use craft\commerce\base\RequestResponseInterface;

/**
 * Result of the refund API call; status 0 means accepted.
 */
class RefundResponse implements RequestResponseInterface
{
    public function __construct(private array $data)
    {
    }

    public function isSuccessful(): bool
    {
        return isset($this->data['status']) && (int)$this->data['status'] === 0;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function getRedirectMethod(): string
    {
        return '';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
        return '';
    }

    public function getTransactionReference(): string
    {
        return (string)($this->data['keks_id'] ?? '');
    }

    public function getCode(): string
    {
        return (string)($this->data['status'] ?? '');
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMessage(): string
    {
        return (string)($this->data['message'] ?? '');
    }

    public function redirect(): void
    {
    }
}
