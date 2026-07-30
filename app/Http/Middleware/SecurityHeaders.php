<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ໃສ່ HTTP security headers ໃຫ້ ທຸກ response (ກັນ clickjacking, MIME-sniffing,
 * referrer leak, ແລະ ບັງຄັບ HTTPS).
 *
 * SECURITY DECISION — CSP is intentionally Content-Security-Policy-**Report-Only**,
 * not enforced. Livewire 3 + Alpine require 'unsafe-inline' + 'unsafe-eval', so a
 * strict enforced CSP would break the UI. Report-Only ships a real, documented CSP
 * baseline (and can collect violation reports) without functional risk. This is a
 * deliberate, accepted low-severity item for pentest — NOT an oversight.
 *
 * To ENFORCE later: move to a per-request nonce ('nonce-...') for scripts/styles,
 * drop 'unsafe-inline'/'unsafe-eval', verify Livewire/Alpine still work, then rename
 * the header to 'Content-Security-Policy'.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=()');

        // HSTS ສະເພາະ ເທິງ HTTPS (ບໍ່ ມີ ຄວາມໝາຍ ເທິງ http)
        if ($request->secure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP — report-only (ບໍ່ ບັງຄັບ): default deny, ອະນຸຍາດ self + fonts.bunny.net,
        // script/style ຕ້ອງ inline+eval ສຳລັບ Livewire/Alpine.
        $headers->set('Content-Security-Policy-Report-Only', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        return $response;
    }
}
