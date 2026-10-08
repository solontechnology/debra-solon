<?php

use App\Http\Middleware\ResolveTenantDatabase;
use App\Http\Middleware\EnsureTenantMenuEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ResolveTenantDatabase::class);
        $middleware->alias([
            'tenant.menu' => EnsureTenantMenuEnabled::class,
        ]);
        $middleware->trustHosts(
            at: fn (): array => array_merge(
                array_map(
                    static fn (string $host): string => '^'.preg_quote($host, '/').'$',
                    config('tenancy.allowed_hosts', [])
                ),
                config('tenancy.allow_local_test_hosts', false)
                    ? ['^(?:[a-z0-9-]+\.)+test$']
                    : []
            ),
            subdomains: false
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
