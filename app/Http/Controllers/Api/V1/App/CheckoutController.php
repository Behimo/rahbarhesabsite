<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Api\V1\Checkout\PrepareHostedCheckoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StartCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

class CheckoutController extends Controller
{
    public function store(StartCheckoutRequest $request, PrepareHostedCheckoutAction $action): JsonResponse
    {
        $session = $action->handle(
            $request->user(),
            $request->user()->currentAccessToken()->getKey(),
            [
                'product_id' => (int) $request->validated('product_id'),
                'coupon_code' => $request->validated('coupon_code') ?: null,
                'gateway' => $request->validated('gateway') ?: null,
            ],
            $request->validated('idempotency_key'),
        );
        $url = URL::temporarySignedRoute('checkout.app-start', $session->expires_at, ['checkout' => $session->id]);

        return response()->json([
            'data' => [
                'checkout_url' => $url,
                // Existing clients opening payment_url also pass through the site.
                'payment_url' => $url,
                'expires_at' => $session->expires_at->toIso8601String(),
            ],
        ], $session->wasRecentlyCreated ? 201 : 200)->header('Cache-Control', 'no-store');
    }
}
