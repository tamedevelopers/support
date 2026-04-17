<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use HeadlessChromium\Page;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Throwable;

trait PdfLinkManagerTrait
{
    private bool $hasTrackedLinks = false;

    private bool $preserveLinksDuringEncryption = false;

    /**
     * Collects {@code a}/{@code area} hit targets in **document** CSS pixels (scroll + layout), splits across virtual
     * print pages using the same paper + margins as {@see \Tamedevelopers\Support\ChromePdf\ChromePdf::buildPdfPrintOptions()}.
     *
     * @param array{
     *     paperWidthIn: float,
     *     paperHeightIn: float,
     *     marginTopIn: float,
     *     marginRightIn: float,
     *     marginBottomIn: float,
     *     marginLeftIn: float
     * } $printLayoutInches
     */
    private function injectLinkTrackingScript(Page $page, array $printLayoutInches): int
    {
        try {
            $n = $page->callFunction(self::linkTrackingInstallerSource(), [$printLayoutInches])->getReturnValue(8000);
            $count = is_int($n) ? $n : (int) $n;
            $this->hasTrackedLinks = $count > 0;

            return $count;
        } catch (Throwable) {
            $this->hasTrackedLinks = false;

            return 0;
        }
    }

    /**
     * @return array{links: list<array<string, mixed>>, meta: array<string, float|int>|null}
     */
    private function extractTrackedLinks(Page $page): array
    {
        $js = <<<'JS'
            (function () {
                var links = window.__pdfLinks;
                var meta = window.__pdfLinkMeta;
                if (!links || !links.length) {
                    return { links: [], meta: meta || null };
                }
                var out = [];
                for (var i = 0; i < links.length; i++) {
                    var L = links[i];
                    out.push({
                        url: String(L.url || ''),
                        x: +L.x || 0,
                        y: +L.y || 0,
                        w: +L.w || 0,
                        h: +L.h || 0,
                        page: +L.page || 1
                    });
                }
                return { links: out, meta: meta || null };
            })()
        JS;

        try {
            $result = $page->evaluate($js)->getReturnValue(5000);
            if (!is_array($result)) {
                return ['links' => [], 'meta' => null];
            }

            $links = $result['links'] ?? [];
            $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : null;

            return [
                'links' => is_array($links) ? $links : [],
                'meta' => $meta,
            ];
        } catch (Throwable) {
            return ['links' => [], 'meta' => null];
        }
    }

    /**
     * @param array{links?: list<array<string, mixed>>, meta?: array<string, float|int>|null}|list<array<string, mixed>> $tracked
     */
    private function encryptWithLinks(
        string $pdfContent,
        array $tracked,
        string $userPassword,
        ?string $ownerPassword = null,
        ?array $blockedPermissions = [],
        int $algorithm = 3,
    ): string {
        $ownerPassword = $ownerPassword ?? $userPassword;
        $permissions = $this->buildPermissionsArray($blockedPermissions);

        [$links, $meta] = self::unpackTrackedLinkBundle($tracked);

        try {
            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0.0, 0.0, 0.0, true);
            $pdf->SetAutoPageBreak(false, 0.0);
            $pdf->SetProtection(
                $permissions,
                $userPassword,
                $ownerPassword,
                $algorithm
            );

            $tempFile = $this->createTempFile($pdfContent);
            $pageCount = $pdf->setSourceFile($tempFile);

            $layoutWpx = max(1.0, (float) ($meta['layoutWidthPx'] ?? 1.0));
            $layoutHpx = max(1.0, (float) ($meta['layoutHeightPx'] ?? 1.0));
            $contentHpx = (float) ($meta['contentHeightPxPerPage'] ?? 0.0);
            if ($contentHpx < 1.0) {
                $contentHpx = max(1.0, $layoutHpx / max(1, $pageCount));
            }

            for ($pageNo = 1; $pageNo <= $pageCount; ++$pageNo) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pageWidth = (float) $size['width'];
                $pageHeight = (float) $size['height'];
                $orientation = $pageWidth > $pageHeight ? 'L' : 'P';
                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                $pdf->useTemplate($templateId);

                $mmPerCssPxX = $pageWidth / $layoutWpx;
                $mmPerCssPxY = $pageHeight / $contentHpx;

                foreach ($links as $link) {
                    $target = (int) ($link['page'] ?? 1);
                    if ($target !== $pageNo) {
                        continue;
                    }
                    $url = (string) ($link['url'] ?? '');
                    if ($url === '') {
                        continue;
                    }
                    $xPx = (float) ($link['x'] ?? 0.0);
                    $yPx = (float) ($link['y'] ?? 0.0);
                    $wPx = max(0.0, (float) ($link['w'] ?? 0.0));
                    $hPx = max(0.0, (float) ($link['h'] ?? 0.0));
                    if ($wPx <= 0.0 || $hPx <= 0.0) {
                        continue;
                    }

                    $xMm = $xPx * $mmPerCssPxX;
                    $wMm = $wPx * $mmPerCssPxX;
                    $hMm = $hPx * $mmPerCssPxY;
                    $yMm = $yPx * $mmPerCssPxY;

                    $xMm = max(0.0, min($pageWidth - $wMm, $xMm));
                    $yMm = max(0.0, min($pageHeight - $hMm, $yMm));

                    try {
                        $pdf->Link($xMm, $yMm, $wMm, $hMm, $url);
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            return $pdf->Output('', 'S');
        } catch (ConversionFailedException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException(
                'Failed to encrypt PDF while preserving links: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array{links?: list<array<string, mixed>>, meta?: array<string, float|int>|null}|list<array<string, mixed>> $tracked
     *
     * @return array{0: list<array<string, mixed>>, 1: array<string, float|int>|null}
     */
    private static function unpackTrackedLinkBundle(array $tracked): array
    {
        if (array_key_exists('links', $tracked)) {
            $links = $tracked['links'];
            $meta = isset($tracked['meta']) && is_array($tracked['meta']) ? $tracked['meta'] : null;

            return [is_array($links) ? $links : [], $meta];
        }

        return [$tracked, null];
    }

    private static function linkTrackingInstallerSource(): string
    {
        return <<<'JS'
            function (printLayout) {
                var PX = 96;
                var pw = +printLayout.paperWidthIn;
                var ph = +printLayout.paperHeightIn;
                var mt = +printLayout.marginTopIn;
                var mr = +printLayout.marginRightIn;
                var mb = +printLayout.marginBottomIn;
                var ml = +printLayout.marginLeftIn;
                var contentW = Math.max(1, (pw - ml - mr) * PX);
                var contentH = Math.max(1, (ph - mt - mb) * PX);

                var de = document.documentElement;
                var body = document.body;
                var layoutW = Math.max(
                    de ? de.scrollWidth : 0,
                    body ? body.scrollWidth : 0,
                    de ? de.clientWidth : 0,
                    1
                );
                var layoutH = Math.max(
                    de ? de.scrollHeight : 0,
                    body ? body.scrollHeight : 0,
                    de ? de.clientHeight : 0,
                    1
                );

                var sx = window.scrollX != null ? window.scrollX : window.pageXOffset;
                var sy = window.scrollY != null ? window.scrollY : window.pageYOffset;

                function docRect(el) {
                    var r = el.getBoundingClientRect();
                    return {
                        x: r.left + sx,
                        y: r.top + sy,
                        w: r.width,
                        h: r.height,
                        top: r.top + sy,
                        bottom: r.bottom + sy
                    };
                }

                function isUsableHref(u) {
                    if (!u || typeof u !== "string") {
                        return false;
                    }
                    var t = u.trim();
                    if (!t || t === "#") {
                        return false;
                    }
                    return t.toLowerCase().indexOf("javascript:") !== 0;
                }

                function pushSlices(url, dr) {
                    if (dr.w <= 0 || dr.h <= 0) {
                        return;
                    }
                    var y0 = dr.top;
                    var y1 = dr.bottom;
                    var startP = Math.floor(y0 / contentH) + 1;
                    var endP = Math.floor((y1 - 1e-9) / contentH) + 1;
                    if (endP < startP) {
                        endP = startP;
                    }
                    for (var p = startP; p <= endP; p++) {
                        var sliceTop = (p - 1) * contentH;
                        var sliceBottom = p * contentH;
                        var iy0 = Math.max(y0, sliceTop);
                        var iy1 = Math.min(y1, sliceBottom);
                        if (iy1 <= iy0) {
                            continue;
                        }
                        window.__pdfLinks.push({
                            url: url,
                            x: dr.x,
                            y: iy0 - sliceTop,
                            w: dr.w,
                            h: iy1 - iy0,
                            page: p
                        });
                    }
                }

                window.__pdfLinks = [];

                var nodes = document.querySelectorAll("a[href]");
                for (var i = 0; i < nodes.length; i++) {
                    var a = nodes[i];
                    var u = "";
                    try {
                        u = a.href || a.getAttribute("href") || "";
                    } catch (e1) {
                        u = "";
                    }
                    if (!isUsableHref(u)) {
                        continue;
                    }
                    pushSlices(u, docRect(a));
                }

                var areas = document.querySelectorAll("area[href]");
                for (var j = 0; j < areas.length; j++) {
                    var ar = areas[j];
                    var u2 = "";
                    try {
                        u2 = ar.href || ar.getAttribute("href") || "";
                    } catch (e2) {
                        u2 = "";
                    }
                    if (!isUsableHref(u2)) {
                        continue;
                    }
                    pushSlices(u2, docRect(ar));
                }

                window.__pdfLinkMeta = {
                    paperWidthIn: pw,
                    paperHeightIn: ph,
                    marginTopIn: mt,
                    marginRightIn: mr,
                    marginBottomIn: mb,
                    marginLeftIn: ml,
                    contentWidthPx: contentW,
                    contentHeightPxPerPage: contentH,
                    layoutWidthPx: layoutW,
                    layoutHeightPx: layoutH
                };

                return window.__pdfLinks.length;
            }
        JS;
    }

    /**
     * Build TCPDF permissions array
     *
     * @param list<string>|null $blockedPermissions
     *
     * @return list<string>
     */
    private function buildPermissionsArray(?array $blockedPermissions): array
    {
        $allowed = [
            'print', 'modify', 'copy', 'annot-forms',
            'fill-forms', 'extract', 'assemble', 'print-high',
        ];

        if ($blockedPermissions === null || $blockedPermissions === []) {
            return $allowed;
        }

        $blockedSet = array_flip($blockedPermissions);

        return array_values(array_filter($allowed, static function (string $perm) use ($blockedSet): bool {
            return !isset($blockedSet[$perm]);
        }));
    }

    private function createTempFile(string $content): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
        if ($tempPath === false) {
            throw new ConversionFailedException('Failed to create temporary file');
        }

        if (file_put_contents($tempPath, $content) === false) {
            throw new ConversionFailedException('Failed to write PDF to temporary file');
        }

        register_shutdown_function(static function () use ($tempPath): void {
            @unlink($tempPath);
        });

        return $tempPath;
    }
}
