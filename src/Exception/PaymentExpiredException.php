<?php

declare(strict_types=1);

namespace OneClickDz\OCPay\Exception;

/**
 * Exception thrown when a payment link has expired (410 errors)
 */
class PaymentExpiredException extends ApiException
{
}

