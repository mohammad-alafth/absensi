<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermissionPilotMiddleware;
use App\Http\Middleware\PermissionEnforcementMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'api/telegram/webhook',
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission.pilot' => PermissionPilotMiddleware::class,
            'permission' => PermissionEnforcementMiddleware::class,
        ]);
    })

    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'pj' => \App\Http\Middleware\PJMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
