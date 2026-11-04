<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Api\V1\Checkout\StartCheckoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StartCheckoutRequest;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function store(StartCheckoutRequest $request, StartCheckoutAction $action): JsonResponse
    {
        $result = $action->handle(
            $request->user(),
            (int) $request->validated('product_id'),
            $request->validated('coupon_code'),
            $request->validated('gateway'),
            $request->validated('idempotency_key'),
            $request->ip(),
            $request->userAgent(),
        );

        $result->order->loadMissing('items');
        OrderResource::prepareMany([$result->order]);

        return response()->json([
            'data' => [
                'order' => new OrderResource($result->order),
                'payment_url' => $result->paymentUrl,
            ],
        ], $result->newlyCreated ? 201 : 200);
    }
}
