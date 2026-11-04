<?php

namespace App\Data\Checkout;

use App\Models\Order;

readonly class CheckoutResult
{
    public function __construct(
        public Order $order,
        public ?string $paymentUrl,
        public bool $newlyCreated = false,
    ) {}
}
