<?php

namespace App\Http\Middleware;

use App\Models\Translation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Applies admin-defined wording fixes (Translation type=replace) to rendered
 * HTML responses across the whole app. Targets are Lao/Thai script so a plain
 * strtr() over the HTML can't collide with ASCII tags/attributes/JS.
 *
 * Skips: streamed/binary responses, non-HTML, and the Translations admin page
 * itself (so the editor shows raw source text, not the replaced version).
 */
class ReplaceTerms
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }

        $ct = $response->headers->get('Content-Type', '');
        if (! str_contains($ct, 'text/html')) {
            return $response;
        }

        if ($request->routeIs('settings.translations')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return $response;
        }

        $replaced = Translation::applyReplacements($content);
        if ($replaced !== $content) {
            $response->setContent($replaced);
        }

        return $response;
    }
}
