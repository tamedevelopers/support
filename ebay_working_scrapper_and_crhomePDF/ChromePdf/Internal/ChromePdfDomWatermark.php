<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Injects full-page fixed watermarks in the live document before {@code Page.printToPDF}.
 * Keeps {@code <a href>} intact so Chromium can emit link annotations (unlike an FPDI/TCPDF re-import).
 */
final class ChromePdfDomWatermark
{
    /**
     * Anonymous function string for {@see \HeadlessChromium\Page::callFunction()}.
     *
     * @return string
     */
    public static function installFunction(): string
    {
        return <<<'JS'
            function(payload) {
                try {
                    var id = '__support_chrome_pdf_wm__';
                    var prev = document.getElementById(id);
                    if (prev) {
                        prev.remove();
                    }
                    if (!payload || typeof payload !== 'object') {
                        return true;
                    }
                    var root = document.createElement('div');
                    root.id = id;
                    root.setAttribute('aria-hidden', 'true');
                    root.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:2147483647;overflow:hidden;margin:0;padding:0;';

                    function box(p, centerImg) {
                        var m = {
                            center: { t: '50%', l: '50%', tr: centerImg ? 'translate(-50%,-50%)' : 'translate(-50%,-50%)' },
                            top_left: { t: '8px', l: '8px', tr: 'none' },
                            top_center: { t: '8px', l: '50%', tr: 'translateX(-50%)' },
                            top_right: { t: '8px', r: '8px', tr: 'none' },
                            center_left: { t: '50%', l: '8px', tr: 'translateY(-50%)' },
                            center_right: { t: '50%', r: '8px', tr: 'translateY(-50%)' },
                            bottom_left: { b: '8px', l: '8px', tr: 'none' },
                            bottom_center: { b: '8px', l: '50%', tr: 'translateX(-50%)' },
                            bottom_right: { b: '8px', r: '8px', tr: 'none' }
                        };
                        return m[p] || m.center;
                    }

                    if (payload.text && payload.text.s) {
                        var t = payload.text;
                        var pos = box(t.p, true);
                        var div = document.createElement('div');
                        var rot = typeof t.a === 'number' ? t.a : 0;
                        div.style.cssText = 'position:absolute;' +
                            (pos.t != null ? 'top:' + pos.t + ';' : '') +
                            (pos.b != null ? 'bottom:' + pos.b + ';' : '') +
                            (pos.l != null ? 'left:' + pos.l + ';' : '') +
                            (pos.r != null ? 'right:' + pos.r + ';' : '') +
                            'transform:' + pos.tr + ' rotate(' + rot + 'deg);' +
                            'opacity:' + (typeof t.o === 'number' ? t.o : 0.14) + ';' +
                            'font-weight:bold;font-family:Helvetica,Arial,sans-serif;color:#808080;' +
                            'font-size:' + (typeof t.fs === 'number' ? t.fs : 44) + 'px;line-height:1;white-space:nowrap;';
                        div.textContent = t.s;
                        root.appendChild(div);
                    }
                    if (payload.image && payload.image.src) {
                        var im = payload.image;
                        var pos2 = box(im.p, true);
                        var img = document.createElement('img');
                        img.src = im.src;
                        img.alt = '';
                        img.style.cssText = 'position:absolute;display:block;' +
                            (pos2.t != null ? 'top:' + pos2.t + ';' : '') +
                            (pos2.b != null ? 'bottom:' + pos2.b + ';' : '') +
                            (pos2.l != null ? 'left:' + pos2.l + ';' : '') +
                            (pos2.r != null ? 'right:' + pos2.r + ';' : '') +
                            'transform:' + pos2.tr + ';' +
                            'opacity:' + (typeof im.o === 'number' ? im.o : 0.16) + ';' +
                            'max-width:' + (typeof im.wPx === 'number' ? im.wPx : 200) + 'px;height:auto;';
                        root.appendChild(img);
                    }
                    document.documentElement.appendChild(root);
                    return true;
                } catch (e) {
                    return false;
                }
            }
        JS;
    }
}
