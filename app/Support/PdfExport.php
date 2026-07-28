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

    /**
     * Base64 data-URI of a public-disk image, centre-cropped to a square.
     *
     * DomPDF ignores `object-fit`, so a portrait photo forced into a square
     * <img> box gets squished (distorted). We crop to a centred square and
     * downscale here so the embedded thumbnail is undistorted (matching the
     * web's object-cover) and the PDF stays small. Falls back to the raw
     * bytes if GD is unavailable, so it degrades to the previous behaviour
     * instead of breaking.
     */
    public static function thumb(?string $path, int $box = 160): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            $abs = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            if (! is_file($abs)) {
                return null;
            }

            $data = file_get_contents($abs);

            if (function_exists('imagecreatefromstring') && ($src = @imagecreatefromstring($data))) {
                $w = imagesx($src);
                $h = imagesy($src);
                $side = min($w, $h);
                $dst = imagecreatetruecolor($box, $box);
                imagecopyresampled(
                    $dst, $src,
                    0, 0,
                    (int) (($w - $side) / 2), (int) (($h - $side) / 2),
                    $box, $box, $side, $side
                );
                ob_start();
                imagejpeg($dst, null, 82);
                $out = ob_get_clean();
                imagedestroy($src);
                imagedestroy($dst);

                return 'data:image/jpeg;base64,'.base64_encode($out);
            }

            return 'data:image/jpeg;base64,'.base64_encode($data);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
