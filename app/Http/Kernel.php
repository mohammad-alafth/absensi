<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'pj' => \App\Http\Middleware\EnsureUserIsPJ::class,
    ];
}