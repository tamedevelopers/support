<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Brief async settle: waits for {@code document.readyState} to leave {@code loading}, then a short timeout.
 * Preloader DOM removal is handled by {@see PreloaderRemovalScript} — this class only provides the timing gap.
 */
final class PageStabilityScript
{
    /**
     * @param int $maxWaitMs soft upper bound (clamped 200–5000)
     * @param bool $lean shorter settle for {@code prioritizeSpeed}
     */
    public static function asSettleExpression(int $maxWaitMs, bool $lean = false): string
    {
        $max = max(200, min(5000, $maxWaitMs));
        $readyCap = $lean ? 600 : 1200;
        $settleMs = $lean ? 30 : 60;

        return "(async function () {"
            . "var t0 = Date.now();"
            . "var cap = Math.min({$readyCap}, {$max});"
            . "while (document.readyState === 'loading' && (Date.now() - t0) < cap) {"
            .     "await new Promise(function (r) { setTimeout(r, 40); });"
            . "}"
            . "await new Promise(function (r) { setTimeout(r, {$settleMs}); });"
            . "return true;"
            . "})()";
    }
}
