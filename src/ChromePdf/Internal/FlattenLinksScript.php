<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Strips navigation attributes from {@code <a>} / {@code <area>} so PDF links are not clickable.
 * Elements stay as {@code <a>} so author CSS still applies.
 */
final class FlattenLinksScript
{
    public static function asExpression(): string
    {
        return <<<'JS'
            (function () {
                try {
                    document.querySelectorAll('a[href],area[href]').forEach(function (el) {
                        try {
                            el.removeAttribute('href');
                            el.removeAttribute('ping');
                            el.removeAttribute('target');
                            el.removeAttribute('download');
                            el.removeAttribute('referrerpolicy');
                        } catch (e) {}
                    });
                } catch (e2) {}
                return true;
            })()
        JS;
    }
}