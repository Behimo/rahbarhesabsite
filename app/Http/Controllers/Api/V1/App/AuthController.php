<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Actions\Api\V1\Auth\SendLoginOtpAction;
use App\Actions\Api\V1\Auth\VerifyLoginOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\SendOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Support\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function sendOtp(SendOtpRequest $request, SendLoginOtpAction $action): JsonResponse
    {
        $challengeId = $action->handle($request->validated('phone'));

        return response()->json([
            'data' => [
                'challenge_id' => $challengeId,
                'expires_in' => (int) config('otp.expires_minutes', 5) * 60,
            ],
        ]);
    }

    public function token(VerifyOtpRequest $request, VerifyLoginOtpAction $action): JsonResponse
    {
        $issued = $action->handle(
            $request->validated('phone'),
            $request->validated('code'),
            $request->validated('challenge_id'),
            $request->validated('device_name'),
            $request->ip(),
        );

        return response()->json([
            'data' => [
                'token' => $issued->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $issued->accessToken->expires_at?->toIso8601String(),
                'user' => new UserResource($issued->accessToken->tokenable),
            ],
        ], 201);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        AuditLog::record('app.token.revoked', [
            'user_id' => $user?->id,
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }
}
