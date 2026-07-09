<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Injected via CDP {@code Page.addScriptToEvaluateOnNewDocument} before any page script runs.
 * Reduces headless/automation signals commonly flagged by Cloudflare and similar WAFs.
 */
final class ChromiumStealthScript
{
    public static function chromeMajorVersion(): string
    {
        return '131';
    }

    public static function chromeUserAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    }

    public static function source(): string
    {
        return <<<'JS'
(() => {
  if (window.__tameChromiumStealthApplied) {
    return;
  }
  window.__tameChromiumStealthApplied = true;

  const patch = (object, property, value) => {
    try {
      Object.defineProperty(object, property, {
        get: () => value,
        configurable: true,
      });
    } catch (e) {}
  };

  patch(navigator, 'webdriver', false);
  patch(navigator, 'languages', ['en-US', 'en']);
  patch(navigator, 'platform', 'Win32');
  patch(navigator, 'hardwareConcurrency', 8);
  patch(navigator, 'deviceMemory', 8);
  patch(navigator, 'maxTouchPoints', 0);

  if (!window.chrome) {
    window.chrome = {};
  }
  window.chrome.runtime = window.chrome.runtime || {};
  window.chrome.loadTimes = window.chrome.loadTimes || function () {};
  window.chrome.csi = window.chrome.csi || function () {};
  window.chrome.app = window.chrome.app || {};

  try {
    const plugin = function () {
      return {
        description: 'Portable Document Format',
        filename: 'internal-pdf-viewer',
        length: 1,
        name: 'Chrome PDF Plugin',
      };
    };
    patch(navigator, 'plugins', [plugin(), plugin()]);
  } catch (e) {}

  try {
    const originalQuery = navigator.permissions.query.bind(navigator.permissions);
    navigator.permissions.query = (parameters) => (
      parameters && parameters.name === 'notifications'
        ? Promise.resolve({ state: Notification.permission })
        : originalQuery(parameters)
    );
  } catch (e) {}

  try {
    const getParameter = WebGLRenderingContext.prototype.getParameter;
    WebGLRenderingContext.prototype.getParameter = function (parameter) {
      if (parameter === 37445) {
        return 'Intel Inc.';
      }
      if (parameter === 37446) {
        return 'Intel Iris OpenGL Engine';
      }
      return getParameter.call(this, parameter);
    };
  } catch (e) {}

  try {
    const elementDescriptor = Object.getOwnPropertyDescriptor(HTMLIFrameElement.prototype, 'contentWindow');
    if (elementDescriptor && elementDescriptor.get) {
      const originalGetter = elementDescriptor.get;
      Object.defineProperty(HTMLIFrameElement.prototype, 'contentWindow', {
        get: function () {
          const win = originalGetter.call(this);
          if (win) {
            try {
              patch(win.navigator, 'webdriver', false);
            } catch (e) {}
          }
          return win;
        },
        configurable: true,
      });
    }
  } catch (e) {}
})();
JS;
    }
}
