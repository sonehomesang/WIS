<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 404 ຖ້າ ໂມດູລ ຖືກ ປິດ ໃນ Settings (feature flag).
 *
 * Maps the route name prefix (before the first '.') to a menu key —
 * e.g. `borrow.create` → `borrow`, `area-inspection` → `area_inspection`.
 * Core / unknown keys are always enabled (Modules::enabled), so only a
 * genuinely toggleable-and-disabled module is blocked. Livewire XHR
 * (route `livewire.update`) maps to `livewire` → always passes.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if ($name) {
            $key = str_replace('-', '_', explode('.', $name)[0]);
            abort_if(! Modules::enabled($key), 404);
        }

        return $next($request);
    }
}
