<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Removes link navigation attributes only — keeps {@code <a>} / {@code <area>} elements so author CSS still applies.
 * Processes deepest anchors first to reduce issues with nested or email/MSO-heavy markup.
 */
final class FlattenLinksScript
{
    public static function asExpression(): string
    {
        return <<<'JS'
            (function () {
                function depth(el) {
                    var d = 0;
                    var n = el;
                    while (n && n.parentNode) {
                        d++;
                        n = n.parentNode;
                    }
                    return d;
                }
                function stripNavAttrs(el) {
                    try {
                        if (!el || el.nodeType !== 1) {
                            return;
                        }
                        el.removeAttribute('href');
                        el.removeAttribute('ping');
                        el.removeAttribute('target');
                        el.removeAttribute('download');
                        el.removeAttribute('referrerpolicy');
                        if (el.hasAttributeNS && el.hasAttributeNS('http://www.w3.org/1999/xlink', 'href')) {
                            el.removeAttributeNS('http://www.w3.org/1999/xlink', 'href');
                        }
                    } catch (e) {}
                }
                var body = document.body;
                if (!body) {
                    return true;
                }
                var list = Array.prototype.slice.call(body.querySelectorAll('a[href], area[href]'));
                list.sort(function (a, b) {
                    return depth(b) - depth(a);
                });
                list.forEach(stripNavAttrs);
                return true;
            })()
        JS;
    }
}
