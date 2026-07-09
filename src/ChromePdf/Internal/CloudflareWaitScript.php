<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Polls the live DOM until Cloudflare / bot interstitials clear or the budget expires.
 */
final class CloudflareWaitScript
{
    public static function asExpression(int $maxWaitMs): string
    {
        $maxWaitMs = max(1000, min(4500, $maxWaitMs));

        return '(async function () {
      const needles = [
        "just a moment",
        "performing security verification",
        "checking your browser",
        "verify you are human",
        "attention required",
      ];
      const htmlNeedles = [
        "cf-challenge",
        "/cdn-cgi/challenge-platform",
        "challenges.cloudflare.com",
      ];
      const deadline = Date.now() + ' . $maxWaitMs . ';
      while (Date.now() < deadline) {
        const title = (document.title || "").toLowerCase();
        const text = (document.body && document.body.innerText ? document.body.innerText : "").toLowerCase();
        const html = (document.documentElement && document.documentElement.outerHTML
          ? document.documentElement.outerHTML
          : "").toLowerCase();
        let blocked = false;
        for (let i = 0; i < needles.length; i++) {
          const n = needles[i];
          if (title.indexOf(n) !== -1 || text.indexOf(n) !== -1) {
            blocked = true;
            break;
          }
        }
        if (!blocked) {
          for (let j = 0; j < htmlNeedles.length; j++) {
            if (html.indexOf(htmlNeedles[j]) !== -1) {
              blocked = true;
              break;
            }
          }
        }
        if (!blocked) {
          return true;
        }
        await new Promise(function (r) { setTimeout(r, 200); });
      }
      return false;
    })();';
    }

    public static function isBlockedExpression(): string
    {
        return '(function () {
      const titleNeedles = [
        "just a moment",
        "performing security verification",
        "checking your browser",
        "verify you are human",
        "attention required",
      ];
      const htmlNeedles = [
        "cf-challenge",
        "/cdn-cgi/challenge-platform",
        "challenges.cloudflare.com",
      ];
      const title = (document.title || "").toLowerCase();
      const text = (document.body && document.body.innerText ? document.body.innerText : "").toLowerCase();
      const html = (document.documentElement && document.documentElement.outerHTML
        ? document.documentElement.outerHTML
        : "").toLowerCase();
      for (let i = 0; i < titleNeedles.length; i++) {
        const n = titleNeedles[i];
        if (title.indexOf(n) !== -1 || text.indexOf(n) !== -1) {
          return true;
        }
      }
      for (let j = 0; j < htmlNeedles.length; j++) {
        if (html.indexOf(htmlNeedles[j]) !== -1) {
          return true;
        }
      }
      return false;
    })();';
    }
}
