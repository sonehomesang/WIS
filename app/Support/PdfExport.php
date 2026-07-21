<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF download helper that fixes the footer "Page X / Y" total.
 *
 * DomPDF (v3) resolves CSS `counter(page)` per page correctly but leaves
 * `counter(pages)` at 0 — so the total never prints. We render once as a
 * probe to learn the real page count, then render again passing that count
 * to the view as `$totalPages` (the footer prints it as a literal beside
 * the working per-page `counter(page)`).
 */
class PdfExport
{
    /** Render $view and stream it as a download with a correct page-count footer. */
    public static function download(string $view, array $data, string $filename): Response
    {
        $total = self::pageCount($view, $data);

        $pdf = Pdf::loadView($view, $data + ['totalPages' => $total])->setPaper('a4');

        return $pdf->download($filename);
    }

    /** Probe render: how many pages does $view produce? */
    public static function pageCount(string $view, array $data): int
    {
        $probe = Pdf::loadView($view, $data + ['totalPages' => null])->setPaper('a4');
        $dom = $probe->getDomPDF();
        $dom->render();

        return max(1, $dom->getCanvas()->get_page_count());
    }
}
