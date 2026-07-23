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
 * Skips: streamed/binary responses and non-HTML. The Translations admin page is
 * NOT skipped — its own chrome (buttons, headers) should reflect overrides like
 * any other page; the editor's source/target cells are wire:model inputs
 * hydrated from the Livewire snapshot, which is never a text node or safe attr,
 * so they always show the raw catalogue text regardless.
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
