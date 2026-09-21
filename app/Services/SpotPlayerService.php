<?php

namespace App\Services;

use App\Models\Course;
use App\Models\SpotplayerLicense;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpotPlayerService
{
    public function isConfigured(): bool
    {
        return ! empty(config('cms.spotplayer.api_key'));
    }

    public function issueLicense(User $user, Course $course, ?int $orderId = null): SpotplayerLicense
    {
        $license = SpotplayerLicense::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'order_id' => $orderId,
                'status' => SpotplayerLicense::STATUS_PENDING,
                'devices_limit' => (int) config('cms.spotplayer.devices_limit', 2),
                'issued_via' => $orderId ? 'purchase' : 'manual',
            ]
        );

        if ($orderId && ! $license->order_id) {
            $license->update(['order_id' => $orderId]);
        }

        if ($license->status === SpotplayerLicense::STATUS_ISSUED && $license->license_key) {
            return $license;
        }

        $license->update(['status' => SpotplayerLicense::STATUS_PENDING]);

        if (! $this->isConfigured() || ! $course->spotplayer_course_id) {
            $license->update(['status' => SpotplayerLicense::STATUS_FAILED]);

            return $license;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('cms.spotplayer.api_key'),
                'Accept' => 'application/json',
            ])->post(rtrim(config('cms.spotplayer.base_url'), '/').'/licenses', [
                'course_id' => $course->spotplayer_course_id,
                'user_name' => $user->name,
                'user_phone' => $user->mobile ?: $user->phone,
                'user_email' => $user->email,
                'watermark' => ['texts' => [['text' => $user->mobile ?: $user->phone]]],
            ]);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                $key = $data['license_key'] ?? $data['key'] ?? $data['license'] ?? null;
                $spotUrl = $data['url'] ?? $data['spot_url'] ?? $data['link'] ?? null;

                if (! $spotUrl && $key) {
                    $spotUrl = rtrim(config('cms.spotplayer.player_url', 'https://app.spotplayer.ir'), '/').'/?license='.$key;
                }

                $license->update([
                    'license_key' => $key,
                    'spot_license_id' => $data['_id'] ?? $data['id'] ?? $license->spot_license_id,
                    'spot_url' => $spotUrl,
                    'devices_limit' => (int) ($data['devices'] ?? $data['devices_limit'] ?? config('cms.spotplayer.devices_limit', 2)),
                    'device_count' => (int) ($data['device_count'] ?? 0),
                    'status' => $key ? SpotplayerLicense::STATUS_ISSUED : SpotplayerLicense::STATUS_FAILED,
                    'api_response' => $data,
                    'issued_at' => $key ? now() : null,
                    'last_verified_at' => now(),
                ]);
            } else {
                $license->update([
                    'status' => SpotplayerLicense::STATUS_FAILED,
                    'api_response' => ['status' => $response->status(), 'body' => $response->json()],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SpotPlayer license error', ['error' => $e->getMessage()]);
            $license->update(['status' => SpotplayerLicense::STATUS_FAILED]);

            return $license->fresh();
        }

        return $license->fresh();
    }

    public function embedUrl(Course $course, User $user, ?string $itemId = null): ?string
    {
        $license = SpotplayerLicense::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', SpotplayerLicense::STATUS_ISSUED)
            ->first();

        if ($license?->spot_url && ! $itemId) {
            return $license->spot_url;
        }

        if (! $license?->license_key) {
            return null;
        }

        $base = rtrim(config('cms.spotplayer.player_url', 'https://app.spotplayer.ir'), '/');
        $params = http_build_query(array_filter([
            'license' => $license->license_key,
            'course' => $course->spotplayer_course_id,
            'item' => $itemId,
        ]));

        return "{$base}/?{$params}";
    }
}
