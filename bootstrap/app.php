<?php

use App\Exceptions\Api\ApiException;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureActiveApiUser;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureApiToken;
use App\Http\Middleware\EnsureAppClient;
use App\Http\Middleware\EnsureCmsAdmin;
use App\Http\Middleware\EnsureUser;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleCmsRedirects;
use App\Http\Middleware\HandleGoneWordpressPaths;
use App\Http\Middleware\NormalizeSeoUrl;
use App\Http\Middleware\PreventIndexingNonProduction;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureActiveAccount::class,
        ]);

        $middleware->append(HandleGoneWordpressPaths::class);
        $middleware->append(HandleCmsRedirects::class);
        $middleware->append(NormalizeSeoUrl::class);
        $middleware->append(PreventIndexingNonProduction::class);

        $proxies = env('TRUSTED_PROXIES');

        if (is_string($proxies) && $proxies !== '') {
            $at = $proxies === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $proxies))));

            if ($at !== [] && $at !== '') {
                $middleware->trustProxies(at: $at);
            }
        }

        $middleware->alias([
            'cms.admin' => EnsureCmsAdmin::class,
            'cms.permission' => EnsureAdminPermission::class,
            'auth.user' => EnsureUser::class,
            'api.token' => EnsureApiToken::class,
            'api.user' => EnsureActiveApiUser::class,
            'app.client' => EnsureAppClient::class,
            'abilities' => CheckAbilities::class,
            'json' => ForceJsonResponse::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
            ], $exception->status);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/v1/app/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'code' => 'validation_failed',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/v1/app/*')) {
                return null;
            }

            return response()->json([
                'message' => 'احراز هویت نشده است.',
                'code' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if (! $request->is('api/v1/app/*') || ! $exception->getPrevious() instanceof MissingAbilityException) {
                return null;
            }

            return response()->json([
                'message' => 'دسترسی این توکن کافی نیست.',
                'code' => 'token_forbidden',
            ], 403);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/v1/app/*')) {
                return null;
            }

            return response()->json([
                'message' => 'تعداد درخواست‌ها بیش از حد مجاز است.',
                'code' => 'too_many_requests',
            ], 429, $exception->getHeaders());
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/v1/app/*')) {
                return null;
            }

            return response()->json([
                'message' => 'موردی پیدا نشد.',
                'code' => 'not_found',
            ], 404);
        });
    })->create();
