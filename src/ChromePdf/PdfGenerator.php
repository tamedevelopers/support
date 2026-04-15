<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Communication\Session;
use HeadlessChromium\Page;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\ChromePdf\ColorScheme;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use Tamedevelopers\Support\ChromePdf\Exception\FontNotFoundException;
use Tamedevelopers\Support\ChromePdf\Exception\InvalidSelectorException;
use Tamedevelopers\Support\ChromePdf\Internal\CombinedPostProcessScript;
use Tamedevelopers\Support\ChromePdf\Internal\FileUri;
use Tamedevelopers\Support\ChromePdf\Internal\FlattenLinksScript;
use Tamedevelopers\Support\ChromePdf\Internal\FloatingElementRemovalScript;
use Tamedevelopers\Support\ChromePdf\Internal\PreloaderRemovalScript;
use Tamedevelopers\Support\ChromePdf\PdfOutput;
use Tamedevelopers\Support\ChromePdf\Traits\FontManagerTrait;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Str;
use Throwable;

/**
 * Fluent builder for HTML → PDF via headless Chromium (chrome-php/chrome).
 *
 * Defaults: {@see prioritizeSpeed()} (no images), {@see waitForWindowLoadEvent()} false (DOMContentLoaded — avoids
 * hanging on pages where {@code load} is delayed by analytics or long-lived requests), and {@see stabilizeBeforeCapture()}.
 * Raise {@see navigationTimeoutMs()} if a site is legitimately slow; {@see waitForWindowLoadEvent(true)} when you need
 * every subresource before capture. Cold Chromium startup is often 1–3s on its own.
 *
 * Use {@see maximumQuality()} for full images and longer navigation/stability budgets.
 *
 * A single Chromium process is reused across {@see generate()} calls (see {@see shutdown()} to release it).
 * Post-navigation work (fonts, optional settle, CMP strip, optional injected CSS) runs in one {@code evaluate()} via
 * {@see CombinedPostProcessScript} to cut WebSocket round-trips. Local {@see fromFile()}/{@see fromHtml()} use
 * {@code DOMContentLoaded} and short budgets for navigation/setHtml.
 * Large remote pages are supported; URL captures use request filtering to avoid tracker-induced timeouts (pattern
 * blocks common tag managers / analytics so the main document can reach {@code DOMContentLoaded} sooner).
 * Common full-page preloaders get one fast {@code evaluate()} pass right after {@code DOMContentLoaded} (no
 * {@code load} wait; no in-page {@code MutationObserver} during hydration — that pattern was far too expensive).
 * For trusted HTML you control, {@see withoutDefaultPostProcessing()} skips stabilize + cookie passes to cut wall time.
 *
 * {@see fromFile()} / {@see fromHtml()} skip stabilize + cookie by default for fast local conversion; call
 * {@see postProcessLocalSources(true)} if you saved remote HTML and still need those passes.
 *
 * {@see clickableLinks(false)} strips {@code href} (and {@code ping}/{@code target}/etc.) from anchors only — same
 * element type so layout and {@code a} styling stay intact; PDF links are not clickable.
 *
 * Remote images: Chromium image loading is **off** by default (bitmap/CSS images from the network are skipped).
 * Call {@see loadRemoteImages(true)} when you need {@code http(s)://} in {@code img}/CSS. Auto font {@code @font-face}
 * maps are applied in-page only when {@code document.body} text matches CJK / Arabic / Cyrillic ranges (see {@see CombinedPostProcessScript}).
 */
final class PdfGenerator
{
    use FontManagerTrait;

    private ?string $sourceMode = null;

    private ?string $sourceValue = null;

    private ?string $selector = null;

    private PaperFormat $paper = PaperFormat::A4;

    private bool $landscape = false;

    /**
     * PDF print margins: {@code none} = 0; {@code default} = ~1 cm all sides;
     * {@code uniform} = {@see $pdfMarginUniformInches} on all sides.
     */
    private string $pdfMarginMode = 'none';

    /** Inches, used when {@see $pdfMarginMode} is {@code uniform}. */
    private float $pdfMarginUniformInches = 0.0;

    /** @var Theme|null merged CSS from {@see theme()}, {@see css()}, and {@see cssFile()} */
    private ?Theme $styles = null;

    private ?string $chromiumBinary = null;

    private bool $autoInjectFonts = true;

    private bool $printBackground = true;

    /**
     * Max time to wait for the initial navigation (ms). Not aggressively capped — short values cause false timeouts
     * on sites where the {@code load} event or network is slow in headless mode.
     */
    private int $navigationTimeoutMs = 30000;

    /**
     * When true (default): no image download; stability budgets stay tighter than {@see maximumQuality()}.
     */
    private bool $prioritizeSpeed = true;

    private ColorScheme $colorScheme = ColorScheme::NoPreference;

    /**
     * When true, waits for the window {@code load} event (can hang or timeout if trackers never finish).
     * Default false uses DOMContentLoaded, which matches most fast real-world loads; use {@see stabilizeBeforeCapture()}.
     */
    private bool $waitForWindowLoadEvent = false;

    /**
     * When true (default), runs an async settle step (document complete, strip common loaders, paint) before PDF.
     */
    private bool $stabilizeBeforeCapture = true;

    /**
     * Budget for {@see stabilizeBeforeCapture()} (ms); capped lower when {@see prioritizeSpeed()} is on.
     */
    private int $stabilityTimeoutMs = 2200;

    /**
     * When true, Chromium ignores TLS certificate errors (useful for intranet HTTPS during development).
     */
    private bool $ignoreCertificateErrors = false;

    /**
     * When true (default), runs client-side removal of common cookie/consent banners before PDF capture.
     * Does not change HTTP cookies — only strips DOM overlays (known CMPs + a small fixed-position heuristic).
     */
    private bool $stripCookiePopupsBeforePdf = true;

    /**
     * When false, navigation attributes are stripped from anchors — see {@see clickableLinks()}.
     */
    private bool $clickableLinks = true;

    /**
     * When true, {@see stabilizeBeforeCapture()} and {@see removeCookiePopups()} also run for {@see fromFile()} /
     * {@see fromHtml()}. Default false keeps local PDF conversion fast (no remote-style DOM walks).
     */
    private bool $postProcessLocalSources = false;

    /**
     * Maps to BrowserFactory {@code enableImages} ({@code --blink-settings=imagesEnabled=false} when false).
     * Default true — call {@see loadRemoteImages(true)} to load remote bitmap/CSS images.
     */
    private bool $enableRemoteImageLoading = true;

    private int $desktopViewportWidth = 1920;

    private int $desktopViewportHeight = 3000;

    private static ?Browser $sharedBrowser = null;

    /** @see browserLaunchKey() */
    private static ?string $sharedBrowserLaunchKey = null;

    private static bool $shutdownHandlerRegistered = false;

    /** Avoids redundant {@code Emulation.*} CDP work on the same target session when the scheme is unchanged. */
    private static ?string $lastColorSchemeEmulationSignature = null;

    /** @var array<string, bool> URL+resource decision cache for {@see handleFetchRequestPaused()}. */
    private static array $fetchBlockCache = [];

    /** @var array<string, string> In-memory cache for built combined evaluate() scripts. */
    private static array $combinedExpressionCache = [];

    /** @var array<string, string>|null */
    private static ?array $cachedAutoFontFaceMap = null;

    private const FETCH_BLOCK_CACHE_CAP = 4096;
    private const PDF_CACHE_VERSION = 'pdf-cache-v1';

    /** Pre-compiled pattern for tracker URLs (hot path in {@see computeFetchShouldBlock()}). */
    private const FETCH_TRACKER_URL_PATTERN = '/analytics|doubleclick|googlesyndication|googletagmanager|google-analytics|gtag\\/|facebook\\.com\\/tr|hotjar|segment\\.(io|com)|fullstory|clarity\\.ms|mixpanel|sentry\\.io|intercom|zendesk|newrelic|pardot|hs-scripts|hs-analytics|adsystem|quantserve|taboola|outbrain|moatads|criteo/i';

    /**
     * Theme/font CSS to apply inside {@see CombinedPostProcessScript} ({@code null} = derive from DOM after URL load).
     */
    private ?string $injectionCssForPostProcess = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Closes the shared Chromium process started by {@see generate()}. Call on long-running workers when PDF
     * generation is finished, or rely on the registered PHP shutdown handler.
     */
    public static function shutdown(): void
    {
        if (self::$sharedBrowser !== null) {
            try {
                self::$sharedBrowser->close();
            } catch (Throwable) {
            }
            self::$sharedBrowser = null;
            self::$sharedBrowserLaunchKey = null;
        }
        self::$lastColorSchemeEmulationSignature = null;
        self::$fetchBlockCache = [];
        self::$combinedExpressionCache = [];
        self::$cachedAutoFontFaceMap = null;
    }

    public function fromUrl(string $url): self
    {
        $this->resetSource();
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ConversionFailedException(sprintf('Invalid URL: %s', $url));
        }
        $this->sourceMode = 'url';
        $this->sourceValue = $url;

        return $this;
    }

    public function fromHtml(string $html): self
    {
        $this->resetSource();
        $this->sourceMode = 'html';
        $this->sourceValue = $html;

        return $this;
    }

    public function fromFile(string $path): self
    {
        $this->resetSource();
        $real = realpath($path);
        if ($real === false || !is_readable($real)) {
            throw new ConversionFailedException(sprintf('HTML file is not readable: %s', $path));
        }
        $this->sourceMode = 'file';
        $this->sourceValue = $real;

        return $this;
    }

    /**
     * When set, only the first matching element is kept in the document body before PDF capture.
     */
    public function selectElement(?string $cssSelector): self
    {
        $this->selector = ($cssSelector !== null && $cssSelector !== '') ? $cssSelector : null;

        return $this;
    }

    /**
     * @param PaperFormat|string $format Named size ({@code A4}, {@code letter}, {@code Legal}, etc.) or a {@see PaperFormat} case
     */
    public function paper(PaperFormat|string $format): self
    {
        $this->paper = $format instanceof PaperFormat ? $format : PaperFormat::parse($format);

        return $this;
    }

    public function landscape(bool $landscape = true): self
    {
        $this->landscape = $landscape;

        return $this;
    }

    /**
     * Controls the white space around the printed page (Chromium {@code Page.printToPDF} margins, in inches).
     *
     * - {@code null}: reset to package default (no margins / 0 on all sides).
     * - {@code true}: apply the package default (~1 cm on each side).
     * - {@code false}: no margins (0 on all sides; content can extend to the physical page edge).
     * - {@code int} or {@code string}: uniform margin on all sides. Bare numbers and {@code Npx} are treated as CSS px
     *   (96 px = 1 in). Also accepts {@code Ncm}, {@code Nmm}, {@code Nin}.
     */
    public function margins(bool|int|string|null $value = null): self
    {
        if ($value === null) {
            $this->pdfMarginMode = 'none';

            return $this;
        }
        if ($value === true) {
            $this->pdfMarginMode = 'default';

            return $this;
        }
        if ($value === false) {
            $this->pdfMarginMode = 'none';

            return $this;
        }
        $this->pdfMarginMode = 'uniform';
        $this->pdfMarginUniformInches = self::parseMarginToInches($value);

        return $this;
    }

    /**
     * Replace injected style bucket with a pre-built {@see Theme} (from {@see Theme::create()}).
     */
    public function theme(Theme $theme): self
    {
        $this->styles = $theme;

        return $this;
    }

    /**
     * Append inline CSS (same backing store as {@see Theme::addCssString()}).
     */
    public function css(string $css): self
    {
        $this->styles ??= Theme::create();
        $this->styles->addCssString($css);

        return $this;
    }

    /**
     * Append CSS from a readable file path.
     */
    public function cssFile(string $path): self
    {
        $this->styles ??= Theme::create();
        $this->styles->addCssFile($path);

        return $this;
    }

    public function chromiumBinary(?string $absolutePath): self
    {
        $this->chromiumBinary = $absolutePath;

        return $this;
    }

    public function autoInjectUnicodeFonts(bool $enabled = true): self
    {
        $this->autoInjectFonts = $enabled;

        return $this;
    }

    public function printBackground(bool $enabled = true): self
    {
        $this->printBackground = $enabled;

        return $this;
    }

    public function navigationTimeoutMs(int $milliseconds): self
    {
        $this->navigationTimeoutMs = max(500, $milliseconds);

        return $this;
    }

    public function prioritizeSpeed(bool $enable = true): self
    {
        $this->prioritizeSpeed = $enable;

        return $this;
    }

    /**
     * Controls whether Chromium loads images (required for {@code http(s)://} in {@code img src} and CSS background images).
     * Default is {@code false}; pass {@code true} to opt in. Web fonts and document text are not gated by this flag.
     */
    public function loadRemoteImages(bool $enable = true): self
    {
        $this->enableRemoteImageLoading = $enable;

        return $this;
    }

    /**
     * Alias for {@see loadRemoteImages()}.
     */
    public function absoluteImageLinks(bool $enable = true): self
    {
        return $this->loadRemoteImages($enable);
    }

    /**
     * Full images and uncapped navigation timeout (within your {@see navigationTimeoutMs()}).
     */
    public function maximumQuality(bool $enable = true): self
    {
        $this->prioritizeSpeed = !$enable;

        return $this;
    }

    /**
     * {@code prefers-color-scheme} emulation. Pass {@see ColorScheme} or a string: {@code light}, {@code dark},
     * {@code no-preference} (aliases: none, system, default, auto). Unknown strings use {@see ColorScheme::NoPreference}.
     */
    public function colorScheme(ColorScheme|string $scheme): self
    {
        $this->colorScheme = $scheme instanceof ColorScheme ? $scheme : ColorScheme::parse($scheme);

        return $this;
    }

    public function waitForWindowLoadEvent(bool $enable = true): self
    {
        $this->waitForWindowLoadEvent = $enable;

        return $this;
    }

    public function stabilizeBeforeCapture(bool $enable = true): self
    {
        $this->stabilizeBeforeCapture = $enable;

        return $this;
    }

    public function stabilityTimeoutMs(int $milliseconds): self
    {
        $this->stabilityTimeoutMs = max(100, $milliseconds);

        return $this;
    }

    public function ignoreCertificateErrors(bool $enable = true): self
    {
        $this->ignoreCertificateErrors = $enable;

        return $this;
    }

    /**
     * Enable or disable stripping cookie / consent UI from the document before printing.
     * When enabled, uses known consent-manager selectors plus a conservative overlay heuristic.
     */
    public function removeCookiePopups(bool $enable = true): self
    {
        $this->stripCookiePopupsBeforePdf = $enable;

        return $this;
    }

    /**
     * When {@code false}, removes {@code href}, {@code ping}, {@code target}, {@code download}, and
     * {@code referrerpolicy} from {@code <a>} / {@code <area>} only — elements stay as {@code <a>} so CSS is unchanged;
     * PDF output has no clickable links.
     */
    public function clickableLinks(bool $enable = true): self
    {
        $this->clickableLinks = $enable;

        return $this;
    }

    public function postProcessLocalSources(bool $enable = true): self
    {
        $this->postProcessLocalSources = $enable;

        return $this;
    }

    /**
     * Skips stabilize + cookie stripping for fastest capture. Use only for trusted URLs/HTML you control
     * (no CMP, no client-only loaders you rely on this pass to remove).
     */
    public function withoutDefaultPostProcessing(bool $skip = true): self
    {
        if ($skip) {
            $this->stabilizeBeforeCapture = false;
            $this->stripCookiePopupsBeforePdf = false;
            $this->postProcessLocalSources = false;
        } else {
            $this->stabilizeBeforeCapture = true;
            $this->stripCookiePopupsBeforePdf = true;
        }

        return $this;
    }

    public function generate(): PdfOutput
    {
        if ($this->sourceMode === null || $this->sourceValue === null) {
            throw new ConversionFailedException(
                'No input configured. Call fromUrl(), fromHtml(), or fromFile() before generate().'
            );
        }

        $browser = $this->acquireSharedBrowser();
        $page = null;
        $this->injectionCssForPostProcess = null;

        try {
            $page = $browser->createPage();
            $this->applyColorSchemeToPage($page);
            $this->applyDesktopViewportForUrlIfNeeded($page);

            match ($this->sourceMode) {
                'url' => $this->loadFromUrlWithBlocking($page),
                'file' => $this->loadFromFile($page),
                'html' => $this->loadFromHtml($page),
            };
            $this->applyDesktopViewportForUrlIfNeeded($page);

            $this->runImmediatePreloaderStripIfApplicable($page);

            [$themeCss, $fontMap] = $this->resolvePostProcessPayload();
            $this->executeCombinedPostProcessing($page, $themeCss, $fontMap);

            if ($this->selector !== null) {
                $this->isolateSelector($page, $this->selector);
            }

            if (!$this->clickableLinks) {
                $this->flattenLinksForPrint($page);
            }

            $pdf = $page->pdf($this->buildPdfPrintOptions());

            $navCap = $this->effectiveNavigationTimeoutMs();
            // printToPDF can take a long time on heavy pages; default wait scales with navigation budget.
            $pdfWait = $this->prioritizeSpeed
                ? min(90000, max(12000, $navCap * 2))
                : min(180000, max(35000, $navCap * 3));
            $data = $pdf->getBase64($pdfWait);
            $raw = base64_decode($data, true);
            if ($raw === false || $raw === '') {
                throw new ConversionFailedException('Chromium returned an empty or invalid PDF payload.');
            }

            return new PdfOutput($raw);
        } catch (InvalidSelectorException | FontNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ConversionFailedException($e->getMessage(), (int) $e->getCode(), $e);
        } finally {
            if ($page !== null) {
                try {
                    $page->close();
                } catch (Throwable) {
                }
            }
        }
    }

    /**
     * @throws ConversionFailedException
     */
    private static function parseMarginToInches(int|string $value): float
    {
        if (is_int($value)) {
            return max(0.0, (float) $value) / 96.0;
        }

        $s = Str::trim($value);
        if ($s === '') {
            throw new ConversionFailedException('Empty margin value.');
        }

        if (preg_match('/^([\d.]+)\s*px$/i', $s, $m) === 1) {
            return max(0.0, (float) $m[1]) / 96.0;
        }
        if (preg_match('/^([\d.]+)\s*cm$/i', $s, $m) === 1) {
            return max(0.0, (float) $m[1]) / 2.54;
        }
        if (preg_match('/^([\d.]+)\s*mm$/i', $s, $m) === 1) {
            return max(0.0, (float) $m[1]) / 25.4;
        }
        if (preg_match('/^([\d.]+)\s*in$/i', $s, $m) === 1) {
            return max(0.0, (float) $m[1]);
        }
        if (preg_match('/^([\d.]+)$/', $s, $m) === 1) {
            return max(0.0, (float) $m[1]) / 96.0;
        }

        throw new ConversionFailedException(sprintf('Unrecognized margin format: %s', $s));
    }

    private function buildPdfPrintOptions(): array
    {
        $opts = [
            'landscape' => $this->landscape,
            'printBackground' => $this->printBackground,
        ];

        if ($this->sourceMode === 'url') {
            $opts['paperWidth'] = PaperFormat::Legal->widthInches();
            $opts['paperHeight'] = PaperFormat::Legal->heightInches();
        } else {
            $opts['paperWidth'] = $this->paper->widthInches();
            $opts['paperHeight'] = $this->paper->heightInches();
        }

        $inch = match ($this->pdfMarginMode) {
            'default' => 1.0 / 2.54,
            'none' => 0.0,
            'uniform' => $this->pdfMarginUniformInches,
            default => null,
        };

        if ($inch !== null) {
            $opts['marginTop'] = $inch;
            $opts['marginBottom'] = $inch;
            $opts['marginLeft'] = $inch;
            $opts['marginRight'] = $inch;
        }

        return $opts;
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildBrowserLaunchConfig(): array
    {
        $env = new ChromiumEnvironment();
        $binary = $this->chromiumBinary ?? $env->resolveChromeBinary();
        $launch = $env->getLaunchOptions();
        if ($this->ignoreCertificateErrors) {
            $launch['ignoreCertificateErrors'] = true;
        }
        $launch['enableImages'] = $this->shouldEnableChromiumImages();
        $launch['keepAlive'] = true;

        return [$binary, $launch];
    }

    private function browserLaunchKey(): string
    {
        [$binary, $launch] = $this->buildBrowserLaunchConfig();

        return implode("\0", [
            (string) $binary,
            ($launch['enableImages'] ?? true) ? '1' : '0',
            ($launch['ignoreCertificateErrors'] ?? false) ? '1' : '0',
        ]);
    }

    private static function registerShutdownHandlerOnce(): void
    {
        if (self::$shutdownHandlerRegistered) {
            return;
        }
        register_shutdown_function(static function (): void {
            PdfGenerator::shutdown();
        });
        self::$shutdownHandlerRegistered = true;
    }

    private function acquireSharedBrowser(): Browser
    {
        self::registerShutdownHandlerOnce();
        $key = $this->browserLaunchKey();
        if (self::$sharedBrowser !== null && self::$sharedBrowserLaunchKey === $key) {
            return self::$sharedBrowser;
        }
        if (self::$sharedBrowser !== null) {
            try {
                self::$sharedBrowser->close();
            } catch (Throwable) {
            }
            self::$sharedBrowser = null;
            self::$sharedBrowserLaunchKey = null;
        }
        [$binary, $launch] = $this->buildBrowserLaunchConfig();
        $factory = new BrowserFactory($binary);
        self::$sharedBrowser = $factory->createBrowser($launch);
        self::$sharedBrowserLaunchKey = $key;

        return self::$sharedBrowser;
    }

    private function shouldStabilizeAfterLoad(): bool
    {
        if (!$this->stabilizeBeforeCapture) {
            return false;
        }

        return $this->sourceMode === 'url' || $this->postProcessLocalSources;
    }

    private function shouldStripCookiesAfterLoad(): bool
    {
        if (!$this->stripCookiePopupsBeforePdf) {
            return false;
        }

        return $this->sourceMode === 'url' || $this->postProcessLocalSources;
    }

    /**
     * One-shot preloader strip for remote captures and “saved HTML” locals — skipped for default file/html.
     */
    private function shouldRunImmediatePreloaderStrip(): bool
    {
        return $this->sourceMode === 'url' || $this->postProcessLocalSources;
    }

    /**
     * One synchronous pass right after {@code DOMContentLoaded} / {@code setHtml} (no {@code load} wait).
     */
    private function runImmediatePreloaderStripIfApplicable(Page $page): void
    {
        if ($this->sourceMode !== 'url' || !$this->shouldRunImmediatePreloaderStrip()) {
            return;
        }
        try {
            $page->evaluate(PreloaderRemovalScript::asImmediateStripExpression())
            ->getReturnValue(
                min(1500, max(400, $this->prioritizeSpeed ? 800 : 1200))
            );
        } catch (Throwable) {
        }
    }

    private function shouldEnableChromiumImages(): bool
    {
        return $this->enableRemoteImageLoading;
    }

    /**
     * Waits for web fonts (when {@code document.fonts} is available) and a paint frame before PDF capture.
     * Runs even when {@see stabilizeBeforeCapture()} is skipped for local sources, so text is not intermittently
     * captured before fonts resolve.
     */
    private function effectiveNavigationTimeoutMs(): int
    {
        return $this->navigationTimeoutMs;
    }

    private function effectiveStabilityTimeoutMs(): int
    {
        if ($this->prioritizeSpeed) {
            return min($this->stabilityTimeoutMs, 1400);
        }

        return min($this->stabilityTimeoutMs, 8000);
    }

    private function evalTimeoutMs(int $maxMs, int $minMs): int
    {
        $base = (int) ($this->effectiveNavigationTimeoutMs() / 2);

        return min($maxMs, max($minMs, $this->prioritizeSpeed ? min($base, 8000) : $base));
    }

    private function flattenLinksForPrint(Page $page): void
    {
        $page->evaluate(FlattenLinksScript::asExpression())->getReturnValue(
            min(15000, max(2500, (int) ($this->effectiveNavigationTimeoutMs() / 4)))
        );
    }

    private function resetSource(): void
    {
        $this->sourceMode = null;
        $this->sourceValue = null;
        $this->injectionCssForPostProcess = null;
    }

    /**
     * Batches prefers-color-scheme emulation, optional auto-dark override, and client hints in one pass.
     * Skips duplicate CDP work when the target session already has the same scheme applied.
     */
    private function applyColorSchemeToPage(Page $page): void
    {
        $session = $page->getSession();
        $signature = $session->getSessionId() . "\0" . $this->colorScheme->value;
        if (self::$lastColorSchemeEmulationSignature === $signature) {
            return;
        }
        self::$lastColorSchemeEmulationSignature = $signature;

        if ($this->colorScheme === ColorScheme::NoPreference) {
            $session->sendMessageSync(new Message('Emulation.setEmulatedMedia', [
                'media' => '',
                'features' => [],
            ]));
        } else {
            $session->sendMessageSync(new Message('Emulation.setEmulatedMedia', [
                'media' => '',
                'features' => [
                    ['name' => 'prefers-color-scheme', 'value' => $this->colorScheme->value],
                ],
            ]));

            if ($this->colorScheme === ColorScheme::Light) {
                try {
                    $session->sendMessageSync(new Message('Emulation.setAutoDarkModeOverride', [
                        'enabled' => false,
                    ]));
                } catch (Throwable) {
                }
            }
        }

        try {
            if ($this->colorScheme === ColorScheme::NoPreference) {
                $page->setExtraHTTPHeaders([]);
            } else {
                $page->setExtraHTTPHeaders([
                    'Sec-CH-Prefers-Color-Scheme' => $this->colorScheme->value,
                ]);
            }
        } catch (Throwable) {
        }
    }

    private function buildThemeCssOnly(): string
    {
        $baseCss = '';
        if ($this->styles === null || $this->styles->isEmpty()) {
            return $baseCss;
        }

        if ($baseCss === '') {
            return $this->styles->toCssString();
        }

        return $baseCss . "\n\n" . $this->styles->toCssString();
    }

    private function navigationLifecycleEvent(): string
    {
        return $this->waitForWindowLoadEvent ? Page::LOAD : Page::DOM_CONTENT_LOADED;
    }

    /**
     * Forces desktop layout for URL captures so responsive sites do not choose a narrow/mobile breakpoint by default.
     */
    private function applyDesktopViewportForUrlIfNeeded(Page $page): void
    {
        if ($this->sourceMode !== 'url') {
            return;
        }

        // Use the library's documented APIs and wait for completion to avoid race conditions.
        try {
            $page->setUserAgent(
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                . '(KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'
            )->await(2000);
        } catch (Throwable) {
        }

        try {
            $page->setDeviceMetricsOverride([
                'width' => $this->desktopViewportWidth,
                'height' => $this->desktopViewportHeight,
                'deviceScaleFactor' => 1,
                'mobile' => false,
            ])->await(2000);
        } catch (Throwable) {
        }

        $session = $page->getSession();
        try {
            $session->sendMessageSync(new Message('Network.setUserAgentOverride', [
                'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                    . '(KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'acceptLanguage' => 'en-US,en;q=0.9',
                'platform' => 'Windows',
                'userAgentMetadata' => [
                    'platform' => 'Windows',
                    'mobile' => false,
                ],
            ]));
        } catch (Throwable) {
        }
        try {
            $session->sendMessageSync(new Message('Emulation.setTouchEmulationEnabled', [
                'enabled' => false,
            ]));
        } catch (Throwable) {
        }
        try {
            $session->sendMessageSync(new Message('Emulation.setEmulatedMedia', [
                'media' => 'screen',
                'features' => [],
            ]));
        } catch (Throwable) {
        }
        try {
            $session->sendMessageSync(new Message('Network.setExtraHTTPHeaders', [
                'headers' => [
                    'Sec-CH-UA-Mobile' => '?0',
                    'Viewport-Width' => (string) $this->desktopViewportWidth,
                ],
            ]));
        } catch (Throwable) {
        }
    }

    private function loadFromUrlWithBlocking(Page $page): void
    {
        $teardown = $this->enableUrlRequestBlocking($page);
        try {
            $this->loadFromUrl($page);
        } finally {
            $teardown();
        }
    }

    /**
     * @return callable Invoked to disable interception (Fetch.disable) and detach the listener.
     */
    private function enableUrlRequestBlocking(Page $page): callable
    {
        $session = $page->getSession();
        $loadRemoteImages = $this->shouldEnableChromiumImages();
        $handler = function (array $params) use ($session, $loadRemoteImages): void {
            $this->handleFetchRequestPaused($session, $params, $loadRemoteImages);
        };
        $session->on('method:Fetch.requestPaused', $handler);
        $session->sendMessageSync(new Message('Fetch.enable', [
            'patterns' => [
                ['urlPattern' => '*', 'requestStage' => 'Request'],
            ],
        ]));

        return function () use ($session, $handler): void {
            try {
                $session->sendMessageSync(new Message('Fetch.disable'));
            } catch (Throwable) {
            }
            $session->removeListener('method:Fetch.requestPaused', $handler);
        };
    }

    private function handleFetchRequestPaused(Session $session, array $params, bool $loadRemoteImages): void
    {
        $requestId = $params['requestId'] ?? '';
        if ($requestId === '') {
            return;
        }
        $url = $params['request']['url'] ?? '';
        $resourceType = $params['resourceType'] ?? '';
        $cacheKey = $url . "\0" . $resourceType . "\0" . ($loadRemoteImages ? '1' : '0');
        if (isset(self::$fetchBlockCache[$cacheKey])) {
            $this->respondToFetchPausedAsync($session, $requestId, self::$fetchBlockCache[$cacheKey]);

            return;
        }

        $block = $this->computeFetchShouldBlock($url, $resourceType, $loadRemoteImages);
        if (count(self::$fetchBlockCache) >= self::FETCH_BLOCK_CACHE_CAP) {
            self::$fetchBlockCache = array_slice(self::$fetchBlockCache, -2048, null, true);
        }
        self::$fetchBlockCache[$cacheKey] = $block;
        $this->respondToFetchPausedAsync($session, $requestId, $block);
    }

    private function computeFetchShouldBlock(string $url, string $resourceType, bool $loadRemoteImages): bool
    {
        if ($url !== '' && preg_match(self::FETCH_TRACKER_URL_PATTERN, $url) === 1) {
            return true;
        }
        if ($resourceType === 'Media') {
            return true;
        }
        if ($resourceType === 'Image' && !$loadRemoteImages) {
            return true;
        }
        if ($resourceType === 'Font') {
            if ($url === '' || str_starts_with($url, 'file:') || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
                return false;
            }
            if (preg_match('#^https?://#i', $url) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Non-blocking CDP send so the Fetch event loop is not stalled by synchronous round-trips.
     */
    private function respondToFetchPausedAsync(Session $session, string $requestId, bool $block): void
    {
        if ($block) {
            $session->sendMessage(new Message('Fetch.failRequest', [
                'requestId' => $requestId,
                'errorReason' => 'BlockedByClient',
            ]));
        } else {
            $session->sendMessage(new Message('Fetch.continueRequest', [
                'requestId' => $requestId,
            ]));
        }
    }

    private function loadFromUrl(Page $page): void
    {
        $this->injectionCssForPostProcess = null;
        $page->navigate($this->sourceValue)->waitForNavigation(
            $this->navigationLifecycleEvent(),
            $this->effectiveNavigationTimeoutMs()
        );
    }

    private function loadFromFile(Page $page): void
    {
        $path = $this->sourceValue;
        $html = file_get_contents($path);
        if ($html === false) {
            throw new ConversionFailedException(sprintf('Could not read file: %s', $path));
        }
        $this->injectionCssForPostProcess = '';
        $merged = self::mergeCssIntoHtmlDocument(
            $html,
            $this->buildThemeCssOnly(),
            self::baseHrefForLocalHtmlFile($path)
        );
        $page->setHtml($merged, 900, Page::DOM_CONTENT_LOADED);
    }

    private function loadFromHtml(Page $page): void
    {
        $html = $this->sourceValue;
        $this->injectionCssForPostProcess = '';
        $merged = self::mergeCssIntoHtmlDocument($html, $this->buildThemeCssOnly(), null);
        $page->setHtml($merged, 900, Page::DOM_CONTENT_LOADED);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function resolvePostProcessPayload(): array
    {
        $fontMap = $this->autoInjectFonts ? $this->cachedAutoFontFaceCssMap() : [];
        if ($this->injectionCssForPostProcess !== null) {
            return [$this->injectionCssForPostProcess, $fontMap];
        }

        return [$this->buildThemeCssOnly(), $fontMap];
    }

    /**
     * @param array<string, string> $fontFaceMap
     */
    private function executeCombinedPostProcessing(Page $page, string $themeCss, array $fontFaceMap): void
    {
        $includeStability = $this->shouldStabilizeAfterLoad();
        $includeCookies = $this->sourceMode === 'url' && $this->shouldStripCookiesAfterLoad();
        $includeFloating = $this->sourceMode === 'url';
        $isLocal = in_array($this->sourceMode, ['file', 'html'], true);
        $speed = $this->prioritizeSpeed;
        $fontRaceMs = $isLocal ? ($speed ? 400 : 500) : ($speed ? 1400 : 2000);
        $budget = $this->effectiveStabilityTimeoutMs();

        $waitForImages = $isLocal;
        $imageWaitMs = $speed ? 900 : 1800;

        $expr = $this->cachedCombinedPostProcessExpression(
            $includeStability,
            $includeCookies,
            $budget,
            $fontRaceMs,
            $themeCss,
            $fontFaceMap,
            $speed,
            $speed ? 12 : 50,
            $includeFloating,
            $waitForImages,
            $imageWaitMs
        );

        // Cookie/floating cleanup on URL pages can exceed a flat 4s on heavier DOMs.
        if ($isLocal) {
            $timeoutMs = $speed ? 4000 : 7000;
        } elseif ($includeCookies || $includeFloating) {
            $timeoutMs = $speed ? 10000 : 14000;
        } else {
            $timeoutMs = $speed ? 5000 : 9000;
        }
        $page->evaluate($expr)->getReturnValue($timeoutMs);
    }

    private function isolateSelector(Page $page, string $selector): void
    {
        $ok = $page->callFunction(
            'function(sel) {
                var el = document.querySelector(sel);
                if (!el) { return false; }
                var clone = el.cloneNode(true);
                while (document.body.firstChild) {
                    document.body.removeChild(document.body.firstChild);
                }
                document.body.appendChild(clone);
                try { clone.scrollIntoView({ block: "start", inline: "nearest" }); } catch (e) {}
                return true;
            }',
            [$selector]
        )->getReturnValue($this->evalTimeoutMs(8000, 2500));

        if ($ok !== true) {
            throw new InvalidSelectorException(
                sprintf('No element matched the selector, or the node could not be isolated: %s', $selector)
            );
        }
    }

    private static function mergeCssIntoHtmlDocument(string $html, string $css, ?string $baseHref): string
    {
        $injectParts = [];
        if ($baseHref !== null && $baseHref !== '') {
            $injectParts[] = '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
        }
        if ($css !== '') {
            $injectParts[] = '<style type="text/css" data-support-chrome-pdf="1">' . $css . '</style>';
        }
        $injection = implode('', $injectParts);
        if ($injection === '') {
            return $html;
        }

        if (preg_match('/<\/head>/i', $html) === 1) {
            return (string) preg_replace('/<\/head>/i', $injection . '</head>', $html, 1);
        }

        if (preg_match('/<head\b[^>]*>/i', $html) === 1) {
            return (string) preg_replace('/<head\b[^>]*>/i', '$0' . $injection, $html, 1);
        }

        return '<!DOCTYPE html><html><head>' . $injection . '</head><body>' . $html . '</body></html>';
    }

    private static function baseHrefForLocalHtmlFile(string $path): string
    {
        $dir = dirname($path);
        $href = FileUri::fromPath($dir);

        return str_ends_with($href, '/') ? $href : ($href . '/');
    }

    /**
     * Cache auto font-face map across requests; font discovery is environment-wide, not document-specific.
     *
     * @return array<string, string>
     */
    private function cachedAutoFontFaceCssMap(): array
    {
        if (self::$cachedAutoFontFaceMap !== null) {
            return self::$cachedAutoFontFaceMap;
        }

        $cacheKey = self::PDF_CACHE_VERSION . '-font-map';
        $cached = self::readPdfCacheJson('font-map', $cacheKey);
        if (is_array($cached)) {
            /** @var array<string, string> $map */
            $map = array_filter($cached, static fn ($v): bool => is_string($v));
            self::$cachedAutoFontFaceMap = $map;

            return self::$cachedAutoFontFaceMap;
        }

        self::$cachedAutoFontFaceMap = $this->buildAutoFontFaceCssMap();
        self::writePdfCacheJson('font-map', $cacheKey, self::$cachedAutoFontFaceMap);

        return self::$cachedAutoFontFaceMap;
    }

    /**
     * Reuses the compiled evaluate() expression across requests for identical flags/theme/font payload.
     *
     * @param array<string, string> $fontFaceMap
     */
    private function cachedCombinedPostProcessExpression(
        bool $includeStability,
        bool $includeCookies,
        int $stabilityBudgetMs,
        int $fontRaceMs,
        string $themeCss,
        array $fontFaceMap,
        bool $leanStability,
        int $paintSettleMs,
        bool $includeFloating,
        bool $waitForImages,
        int $imageWaitMs
    ): string {
        $keyPayload = [
            'v' => self::PDF_CACHE_VERSION,
            'sig' => self::combinedScriptCacheSignature(),
            'stability' => $includeStability,
            'cookies' => $includeCookies,
            'budget' => $stabilityBudgetMs,
            'fontRace' => $fontRaceMs,
            'theme' => hash('sha256', $themeCss),
            'fonts' => hash('sha256', json_encode($fontFaceMap) ?: ''),
            'lean' => $leanStability,
            'paint' => $paintSettleMs,
            'floating' => $includeFloating,
            'waitImages' => $waitForImages,
            'imageWait' => $imageWaitMs,
        ];
        $cacheKey = hash('sha256', json_encode($keyPayload) ?: serialize($keyPayload));

        if (isset(self::$combinedExpressionCache[$cacheKey])) {
            return self::$combinedExpressionCache[$cacheKey];
        }

        $cached = self::readPdfCacheText('combined', $cacheKey);
        if (is_string($cached) && $cached !== '') {
            self::$combinedExpressionCache[$cacheKey] = $cached;

            return $cached;
        }

        $expr = CombinedPostProcessScript::asExpression(
            $includeStability,
            $includeCookies,
            $stabilityBudgetMs,
            $fontRaceMs,
            $themeCss,
            $fontFaceMap,
            $leanStability,
            $paintSettleMs,
            $includeFloating,
            $waitForImages,
            $imageWaitMs
        );

        self::$combinedExpressionCache[$cacheKey] = $expr;
        self::writePdfCacheText('combined', $cacheKey, $expr);

        return $expr;
    }

    private static function pdfCacheDir(string $segment = ''): string
    {
        $base = rtrim(Server::formatWithBaseDirectory('storage/pdf'), '/\\');
        if ($segment === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . trim($segment, '/\\');
    }

    private static function ensurePdfCacheDir(string $segment = ''): bool
    {
        $dir = self::pdfCacheDir($segment);
        File::makeDirectory($dir);

        return File::isDirectory($dir);
    }

    private static function readPdfCacheJson(string $segment, string $key): ?array
    {
        $raw = self::readPdfCacheRaw($segment, $key, '.json.gz');
        if ($raw === null || $raw === '') {
            return null;
        }

        $json = function_exists('gzdecode') ? @gzdecode($raw) : false;
        $payload = ($json !== false && $json !== null) ? $json : $raw;
        $decoded = json_decode((string) $payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function writePdfCacheJson(string $segment, string $key, array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return;
        }

        $raw = function_exists('gzencode') ? gzencode($json, 6) : $json;
        self::writePdfCacheRaw($segment, $key, '.json.gz', (string) $raw);
    }

    private static function readPdfCacheText(string $segment, string $key): ?string
    {
        $raw = self::readPdfCacheRaw($segment, $key, '.txt.gz');
        if ($raw === null || $raw === '') {
            return null;
        }

        $text = function_exists('gzdecode') ? @gzdecode($raw) : false;

        return ($text !== false && $text !== null) ? (string) $text : (string) $raw;
    }

    private static function writePdfCacheText(string $segment, string $key, string $payload): void
    {
        $raw = function_exists('gzencode') ? gzencode($payload, 6) : $payload;
        self::writePdfCacheRaw($segment, $key, '.txt.gz', (string) $raw);
    }

    private static function readPdfCacheRaw(string $segment, string $key, string $suffix): ?string
    {
        $path = self::pdfCacheDir($segment) . DIRECTORY_SEPARATOR . $key . $suffix;
        if (!File::exists($path)) {
            return null;
        }

        $raw = File::get($path);

        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    private static function writePdfCacheRaw(string $segment, string $key, string $suffix, string $payload): void
    {
        if (!self::ensurePdfCacheDir($segment)) {
            return;
        }

        File::put(self::pdfCacheDir($segment) . DIRECTORY_SEPARATOR . $key . $suffix, $payload);
    }

    private static function combinedScriptCacheSignature(): string
    {
        $files = [
            __DIR__ . DIRECTORY_SEPARATOR . 'Internal' . DIRECTORY_SEPARATOR . 'CombinedPostProcessScript.php',
            __DIR__ . DIRECTORY_SEPARATOR . 'Internal' . DIRECTORY_SEPARATOR . 'PageStabilityScript.php',
            __DIR__ . DIRECTORY_SEPARATOR . 'Internal' . DIRECTORY_SEPARATOR . 'CookiePopupRemovalScript.php',
            __DIR__ . DIRECTORY_SEPARATOR . 'Internal' . DIRECTORY_SEPARATOR . 'FloatingElementRemovalScript.php',
        ];

        $parts = [];
        foreach ($files as $file) {
            $parts[] = is_file($file) ? (string) @filemtime($file) : '0';
        }

        return implode(':', $parts);
    }

}
