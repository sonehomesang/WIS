<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ໃສ່ HTTP security headers ໃຫ້ ທຸກ response (ກັນ clickjacking, MIME-sniffing,
 * referrer leak, ແລະ ບັງຄັບ HTTPS). CSP ໃສ່ ແບບ report-only ກ່ອນ — ບໍ່ ທຳລາຍ
 * ການ ໃຊ້ ງານ (Livewire/Alpine ຕ້ອງ inline+eval) ແຕ່ ສະແດງ ວ່າ ມີ ພื้นฐาน CSP.
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

        // HSTS ສະເພาະ ເທິງ HTTPS (ບໍ່ ມີ ຄວາມໝາຍ ເທິງ http)
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
