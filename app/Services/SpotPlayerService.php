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
            ['order_id' => $orderId, 'status' => SpotplayerLicense::STATUS_PENDING]
        );

        if ($license->status === SpotplayerLicense::STATUS_ISSUED && $license->license_key) {
            return $license;
        }

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
                'user_phone' => $user->phone,
                'user_email' => $user->email,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $license->update([
                    'license_key' => $data['license_key'] ?? $data['key'] ?? null,
                    'status' => SpotplayerLicense::STATUS_ISSUED,
                    'api_response' => $data,
                    'issued_at' => now(),
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
