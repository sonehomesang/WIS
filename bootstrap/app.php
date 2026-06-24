<?php

use App\Http\Middleware\ReplaceTerms;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Admin wording overrides applied to rendered HTML (Phase 6.11).
        $middleware->appendToGroup('web', ReplaceTerms::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
