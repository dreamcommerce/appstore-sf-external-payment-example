<?php

namespace App\Exception\Payment;

use RuntimeException;
use Throwable;

class PaymentApiException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
