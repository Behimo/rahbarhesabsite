<?php

namespace App\Actions\Api\V1\Checkout;

use App\Exceptions\Api\ApiException;
use App\Models\AppCheckoutSession;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class PrepareHostedCheckoutAction
{
    public function __construct(private StartCheckoutAction $checkout) {}

    public function handle(User $user, int $tokenId, array $payload, string $key): AppCheckoutSession
    {
        $fingerprint = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $session = AppCheckoutSession::query()->where('user_id', $user->id)->where('idempotency_key', $key)->first();

        if (! $session) {
            $this->checkout->purchasableProduct($user, $payload['product_id']);

            try {
                $session = AppCheckoutSession::query()->create([
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                    'idempotency_key' => $key,
                    'fingerprint' => $fingerprint,
                    'payload' => $payload,
                    'expires_at' => now()->addMinutes(10),
                ]);
            } catch (UniqueConstraintViolationException) {
                $session = AppCheckoutSession::query()->where('user_id', $user->id)->where('idempotency_key', $key)->firstOrFail();
            }
        }

        if (! hash_equals($session->fingerprint, $fingerprint)) {
            throw new ApiException('این کلید تکرار برای خرید دیگری استفاده شده است.', 'idempotency_conflict', 409);
        }

        // A fresh authenticated app request can renew an expired purchase link.
        $session->forceFill(['token_id' => $tokenId, 'expires_at' => now()->addMinutes(10)])->save();

        return $session;
    }
}
