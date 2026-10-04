<?php

use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureApiToken;
use App\Http\Middleware\EnsureCmsAdmin;
use App\Http\Middleware\EnsureUser;
use App\Http\Middleware\HandleCmsRedirects;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(HandleCmsRedirects::class);

        $middleware->alias([
            'cms.admin' => EnsureCmsAdmin::class,
            'cms.permission' => EnsureAdminPermission::class,
            'auth.user' => EnsureUser::class,
            'api.token' => EnsureApiToken::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
