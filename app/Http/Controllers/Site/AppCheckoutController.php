<?php

namespace App\Http\Controllers\Site;

use App\Actions\Api\V1\Checkout\StartCheckoutAction;
use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AppCheckoutSession;
use App\Models\User;
use App\Services\Payments\PaymentReturnRedirector;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AppCheckoutController extends Controller
{
    public function __invoke(Request $request, AppCheckoutSession $checkout, StartCheckoutAction $action, PaymentReturnRedirector $returns): Response
    {
        // Browsing/previewing with HEAD must never initiate a payment.
        if ($request->isMethod('HEAD')) {
            return response('', 200, $this->headers());
        }

        try {
            return Cache::lock('app:hosted-checkout:'.$checkout->id, 120)->block(5, function () use ($request, $checkout, $action, $returns) {
                $checkout->refresh();
                $user = User::query()->find($checkout->user_id);
                $token = PersonalAccessToken::query()->find($checkout->token_id);
                $ttl = (int) config('sanctum.expiration');
                if ($checkout->expires_at->isPast() || ! $user || $user->isBlocked()
                    || ! $token || ! $token->tokenable?->is($user) || ! $token->can('app')
                    || $token->expires_at?->isPast() || ($ttl > 0 && $token->created_at->lte(now()->subMinutes($ttl)))) {
                    return $this->failure('لینک خرید منقضی یا غیرفعال شده است. از اپلیکیشن دوباره خرید را شروع کنید.', 403);
                }

                $payload = $checkout->payload;
                $result = $action->handle($user, $payload['product_id'], $payload['coupon_code'], $payload['gateway'],
                    $checkout->idempotency_key, $request->ip(), $request->userAgent());

                $response = $result->paymentUrl
                    ? redirect()->away($result->paymentUrl)
                    : $returns->afterCallback($result->order, true);

                return $response->withHeaders($this->headers());
            });
        } catch (ApiException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        } catch (LockTimeoutException) {
            return $this->failure('خرید در حال پردازش است. چند لحظه دیگر دوباره تلاش کنید.', 409);
        }
    }

    private function failure(string $message, int $status): Response
    {
        return response()->view('pages.shop.app-checkout-error', compact('message'), $status, $this->headers());
    }

    private function headers(): array
    {
        return ['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow'];
    }
}
