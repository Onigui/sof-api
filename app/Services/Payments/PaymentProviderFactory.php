<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentProviderFactory
{
    public function make(string $provider): PaymentProvider
    {
        return match ($provider) {
            'manual' => new ManualPaymentProvider(),
            default => throw new InvalidArgumentException('Provider not supported.'),
        };
    }
}
