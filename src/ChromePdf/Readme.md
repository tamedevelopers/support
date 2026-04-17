# ChromePdf

ChromePdf is a high-performance HTML → PDF generator for PHP powered by a real headless Chromium browser via [`chrome-php/chrome`](https://github.com/chrome-php/chrome).

The goal of this library is to convert URLs, HTML strings, and local HTML files into **accurate, production-ready PDF documents** that preserve real browser rendering (Flexbox, Grid, JavaScript, remote assets where enabled, and complex layouts).

ChromePdf is designed to be:

- Simple to use across Windows, Linux, and macOS
- Auto-detecting of Chromium/Chrome binaries in common environments
- Suitable for VPS, shared hosting (where browsers are allowed), and local development
- Fast through **persistent Chromium process reuse** across `generate()` calls
- Fluent: one builder chain from input → `PdfOutput`

### Core objective

A single fluent API that:

- Accepts **URL**, **HTML string**, or **local HTML file**
- Uses real browser rendering for maximum fidelity
- Optionally applies **native Chromium headers/footers**, **watermarks**, **encryption**, **PDF/A**, **metadata**, **merge**, and **reprocess** pipelines when FPDI + TCPDF are installed

---

## Documentation

### Getting started

- [Requirements](#requirements)
- [Composer packages for ChromePdf](#composer-packages-for-chromepdf)
- [Installation](#installation)
- [Enable PHP extensions](#enable-php-extensions)
- [Development environment (auto-detect Chrome)](#development-environment)
- [Installing Chromium (if Chrome is not installed)](#installing-chromium-if-chrome-is-not-installed)
  - [Download Chromium for Windows](#download-chromium-for-windows)
  - [Installation on Linux (terminal)](#installation-on-linux-terminal)
  - [Example folder structure](#example-folder-structure)
- [Important hosting notes](#important-hosting-notes)

### Usage

- [Important defaults](#important-defaults)
- [Basic usage](#basic-usage)
- [Full example](#full-example)
- [Sources](#sources)
  - [`fromUrl()`](#fromurl)
  - [`fromHtml()`](#fromhtml)
  - [`fromFile()`](#fromfile)
- [Page settings](#page-settings)
  - [`paper()`](#paper)
  - [`landscape()`](#landscape)
  - [`margin()`](#margin)
  - [`margins()`](#margins)
- [Styling](#styling)
  - [`theme()`](#theme)
  - [`css()`](#css)
  - [`cssFile()`](#cssfile)
- [DOM processing](#dom-processing)
  - [`printFromElement()`](#printfromelement)
  - [`hideElements()`](#hideelements)
  - [`removeCookiePopups()`](#removecookiepopups)
  - [`clickableLinks()`](#clickablelinks)
- [Rendering and performance](#rendering-and-performance)
  - [`prioritizeSpeed()`](#prioritizespeed)
  - [`maximumQuality()`](#maximumquality)
  - [`loadRemoteImages()`](#loadremoteimages)
  - [`absoluteImageLinks()`](#absoluteimagelinks)
  - [`waitForWindowLoadEvent()`](#waitforwindowloadevent)
  - [`stabilizeBeforeCapture()`](#stabilizebeforecapture)
  - [`stabilityTimeoutMs()`](#stabilitytimeoutms)
  - [`navigationTimeoutMs()`](#navigationtimeoutms)
  - [`withoutDefaultPostProcessing()`](#withoutdefaultpostprocessing)
  - [`postProcessLocalSources()`](#postprocesslocalsources)
- [Fonts](#fonts)
- [Print appearance](#print-appearance)
- [Chromium settings](#chromium-settings)
  - [`chromiumBinary()`](#chromiumbinary)
  - [`ignoreCertificateErrors()`](#ignorecertificateerrors)
- [Native PDF header and footer](#native-pdf-header-and-footer)
- [Watermarks](#watermarks)
- [Encryption, PDF/A, and metadata](#encryption-pdfa-and-metadata)
- [Merge and reprocess](#merge-and-reprocess)
- [Links, encryption, and TCPDF rebuild](#links-encryption-and-tcpdf-rebuild)
- [`generate()` and `PdfOutput`](#generate-and-pdfoutput)
- [Shutdown browser](#shutdown-browser)
- [Exceptions](#exceptions)
- [License](#license)

---

## Requirements

- **PHP 8.1+**
- **Google Chrome or Chromium** (binary on the server)
- **Composer**
- PHP **sockets** extension (for `chrome-php` WebSocket IPC)

---

## Composer packages for ChromePdf

In the `tamedevelopers/support` package, ChromePdf-related dependencies are suggested / dev-scoped. For production PDF generation, install at least:

```bash
composer require chrome-php/chrome chrome-php/wrench
```

Ensure `ext-sockets` is enabled in `php.ini`.

For **merge**, **password protection**, **PDF/A**, and heavy **watermark** paths that rebuild PDFs with FPDI + TCPDF:

```bash
composer require setasign/fpdi tecnickcom/tcpdf
```

Use **`setasign/fpdi`** with **`tecnickcom/tcpdf`** (FPDI’s TCPDF adapter) 
---

## Installation

```bash
composer require tamedevelopers/support
```

Then add the Chrome/FPDI packages as above if they are not already present in your project.

---

## Enable PHP extensions

ChromePdf talks to Chromium over WebSockets — enable **sockets**.

In `php.ini`, uncomment:

```ini
extension=sockets
```

Restart PHP / the web server (XAMPP, WAMP, MAMP, Linux `php-fpm`, etc.).

---

## Development environment

If **Google Chrome** is already installed, you usually **do not** need a separate Chromium download. ChromePdf looks for common install paths.

**Windows**

- `C:\Program Files\Google\Chrome\Application\chrome.exe`
- `C:\Program Files (x86)\Google\Chrome\Application\chrome.exe`

**Linux**

- `/usr/bin/google-chrome`
- `/usr/bin/google-chrome-stable`
- `/usr/bin/chromium`
- `/usr/bin/chromium-browser`

**macOS**

- `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`
- `/Applications/Chromium.app/Contents/MacOS/Chromium`

Configure `chromiumBinary()` manually when Chrome is missing, you use a portable build, or you pin a specific binary.

---

## Installing Chromium (if Chrome is not installed)

ChromePdf needs a real Chromium/Chrome executable.

| Platform | Typical binary |
|----------|----------------|
| Windows  | `chrome.exe` |
| Linux    | `chromium` or `google-chrome-stable` |
| macOS    | `Google Chrome` or `Chromium` app bundle |

### Download Chromium for Windows

- https://download-chromium.appspot.com/
- https://chromium.woolyss.com/ (ZIP portable build, not a random `.exe` installer)

Pick the CPU architecture that matches your server.

### Installation on Linux (terminal)

Example (Debian/Ubuntu-style) for Google Chrome:

```bash
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt update
sudo apt install -y ./google-chrome-stable_current_amd64.deb
google-chrome-stable --version
which google-chrome-stable
```

### Example folder structure

Place a portable binary anywhere readable by PHP, then point the builder at it:

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

ChromePdf::create()
    ->chromiumBinary(base_path('bin/chromium/chrome.exe')); // Windows

ChromePdf::create()
    ->chromiumBinary('/home/user/bin/chromium/chrome'); // Linux
```

---

## Important hosting notes

- The runtime must allow **`exec()` / `proc_open()`** (how `chrome-php` launches the browser).
- **512 MB+ RAM** is a practical minimum for headless Chrome.
- Many **shared hosts** block browser processes — verify policy before relying on ChromePdf in production.
---

## Important defaults

Behaviour worth knowing before tuning:

- **`prioritizeSpeed(true)`** is the default profile (tighter stability budgets, tuned for typical pages). Use **`maximumQuality(true)`** when you need heavier image/network behaviour and higher timeouts.
- **Navigation** for remote URLs uses **`DOMContentLoaded`** by default, not the window **`load`** event (avoids hanging on slow analytics). Enable **`waitForWindowLoadEvent(true)`** only when you truly need all subresources first.
- **`stabilizeBeforeCapture(true)`** (default) runs a short client-side settle step for remote URLs; local **`fromFile()` / `fromHtml()`** skip stabilize and cookie stripping **unless** you call **`postProcessLocalSources(true)`**.
- **`removeCookiePopups(true)`** strips common consent overlays from the **DOM** (not HTTP cookies).
- **`clickableLinks(true)`** keeps `<a>` / `<area>` navigation attributes so Chromium can emit PDF link annotations. **`clickableLinks(false)`** strips those attributes before print (layout unchanged, links not clickable).
- **Encryption / PDF-A / some watermark paths** re-embed the PDF via TCPDF; that rebuild **normally drops** Chromium’s link annotations — unless you use **encryption together with `clickableLinks(true)`**, in which case the library can **re-apply** link rectangles after encryption (see [Links, encryption, and TCPDF rebuild](#links-encryption-and-tcpdf-rebuild)).

---

## Basic usage

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

$output = ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();

$output->view();
```

---

## Full example

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

$output = ChromePdf::create()
    ->fromFile('invoice/template.html')
    ->paper('A4')
    ->printFromElement('.body')
    ->margins(20)
    ->clickableLinks(false)
    ->generate();

$output->inline();
// $output->view();
// $output->download('invoice.pdf');
// $output->save('storage/invoices/invoice.pdf');
```

---

## Sources

Call **exactly one** of `fromUrl`, `fromHtml`, or `fromFile` before `generate()`.

### `fromUrl`

```php
ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();
```

Invalid URLs throw `ConversionFailedException`.

### `fromHtml`

```php
ChromePdf::create()
    ->fromHtml('<h1>Hello</h1><p>你好世界</p>')
    ->generate();
```

### `fromFile`

Absolute or project-relative path; the file must be **readable**.

```php
ChromePdf::create()
    ->fromFile('/path/to/file.html')
    ->generate();
```

---

## Page settings

### `paper`

Named sizes (case-insensitive strings) or a `PaperFormat` enum case:

`A0` … `A6`, `B4`, `B5`, `Letter`, `Legal`, `Ledger`, `Tabloid`, `Executive`.

```php
->paper('A4')
->paper(\Tamedevelopers\Support\ChromePdf\PaperFormat::Letter)
```

### `landscape`

```php
->landscape();        // true
->landscape(false);   // portrait
```

### `margin`

Uniform margin shorthand (all sides):

- `null` or `false` — **no** PDF margins (0).
- `true` — library default (~1 cm each side).
- `int` or string (`10`, `10mm`, `1cm`, `0.5in`, `12px`) — uniform value. Bare numbers and `Npx` are treated as **CSS px** (96 px = 1 in).

```php
->margin(true);
->margin(20);
->margin('12mm');
```

### `margins`

Explicit top, right, bottom, left (same units as `margin()`).

```php
->margins(10, 12, 10, 12);
```

---

## Styling

### `theme`

Replace the internal style bucket with a reusable `Theme` object (advanced). Most apps use `css()` / `cssFile()` instead.

```php
use Tamedevelopers\Support\ChromePdf\Theme;

$theme = Theme::create()
    ->addCssString('body { font-family: system-ui; }');

ChromePdf::create()
    ->theme($theme);
```

### `css`

Append inline CSS (merged into the document before capture).

```php
->css('body { font-size: 14pt; }')
```

### `cssFile`

Append CSS from a readable file path.

```php
->cssFile('/path/to/print.css')
```

---

## DOM processing

### `printFromElement`

When set, only the **first matching element** is kept in the document body before capture (CSS selector).

```php
->printFromElement('#invoice')
->printFromElement(null) // clear
```

### `hideElements`

**Removes** every element matching any of the given selectors from the live DOM before capture (opposite of “print only this element”).

```php
->hideElements('#ads', '.cookie-banner');
// or variadic list / nested arrays (flattened internally)
```

### `removeCookiePopups`

When `true` (default for remote flows), strips common cookie/consent overlays.

```php
->removeCookiePopups(false);
```

### `clickableLinks`

When `false`, strips `href`, `ping`, `target`, `download`, and `referrerpolicy` from `<a>` / `<area>` only (tag names unchanged so CSS still applies).

```php
->clickableLinks(false);
```

---

## Rendering and performance

### `prioritizeSpeed`

Default **on**: shorter stability cap, tuned for fast capture.

```php
->prioritizeSpeed(true);
```

### `maximumQuality`

Turns **off** `prioritizeSpeed` (full quality profile: images on path, higher stability budget).

```php
->maximumQuality();
```

### `loadRemoteImages`

Controls Chromium **bitmap / CSS image** loading from the network. Toggle when pages are image-heavy.

```php
->loadRemoteImages(true);
->loadRemoteImages(false);
```

### `absoluteImageLinks`

Alias of **`loadRemoteImages()`**.

### `waitForWindowLoadEvent`

`true` waits for the window `load` event (can hang on trackers). Default **`false`** uses `DOMContentLoaded`.

```php
->waitForWindowLoadEvent(true);
```

### `stabilizeBeforeCapture`

Client-side settle (fonts/paint/loader strip) before `printToPDF`. Default **`true`** for URLs; local HTML/file skips unless `postProcessLocalSources(true)`.

```php
->stabilizeBeforeCapture(false);
```

### `stabilityTimeoutMs`

Budget for stabilization (ms); capped lower when `prioritizeSpeed` is on.

```php
->stabilityTimeoutMs(4000);
```

### `navigationTimeoutMs`

Initial navigation budget (ms), minimum 500.

```php
->navigationTimeoutMs(60000);
```

### `withoutDefaultPostProcessing`

Fast path: disables stabilize + cookie stripping (and does not force local post-process). Use only for **trusted** HTML you control.

```php
->withoutDefaultPostProcessing();
```

### `postProcessLocalSources`

When `true`, **`fromFile` / `fromHtml`** also run the same stabilize + cookie passes as remote URLs (useful for saved offline copies of remote pages).

```php
->postProcessLocalSources(true);
```

---

## Fonts

### `autoInjectUnicodeFonts`

Controls automatic `@font-face` injection for certain Unicode scripts when the DOM text matches (CJK / Arabic / Cyrillic ranges). Default **on**.

```php
->autoInjectUnicodeFonts(false);
```

---

## Print appearance

### `printBackground`

Whether background graphics are printed (Chromium `printBackground`).

```php
->printBackground(false);
```

### `colorScheme`

Emulates `prefers-color-scheme`. Accepts `ColorScheme` enum or string: `light`, `dark`, `no-preference` (aliases like `auto` / `system` map to no preference).

```php
use Tamedevelopers\Support\ChromePdf\ColorScheme;

->colorScheme(ColorScheme::Dark);
->colorScheme('dark');
```

---

## Chromium settings

### `chromiumBinary`

Absolute path to `chrome` / `chromium` / `Google Chrome` executable.

```php
->chromiumBinary('/usr/bin/chromium');
```

### `ignoreCertificateErrors`

Ignore TLS certificate errors (development / intranet HTTPS only).

```php
->ignoreCertificateErrors(true);
```

---

## Native PDF header and footer

Chromium’s built-in **`displayHeaderFooter`** templates (HTML snippets). Special **classes** are replaced by Chrome: `date`, `title`, `url`, `pageNumber`, `totalPages`. Avoid using those class names on arbitrary spans if you want literal text.

```php
->headerHtml('<div style="font-size:9px;width:100%;text-align:center;"><span class="title"></span></div>')
->footerHtml() // default page x / y
`->headerLeft()` / `->headerRight()`  
`->footerLeft()` / `->footerRight()`
->headerFooterBackground('#f5f5f5', '#f5f5f5')
->headerFooterColor('#333333', '#333333')
->headerFooterSpacing(4, 4)
->headerFooterHeight(40, 30)
->footerEdgeOffset('-2px')
```

---

## Watermarks

**DOM watermarks** (painted before `printToPDF`) preserve Chromium links when no encrypt/PDF-A rebuild is required. Heavy options may still route through TCPDF (see pipeline code).

### Text

```php
->textWatermark('CONFIDENTIAL', opacity: 0.14, angleDegrees: -45.0, fontSizePt: 44.0)
->textWatermarkPosition('center')           // string
->textWatermarkPosition(WatermarkPosition::BottomRight)
```

### Image

```php
->imageWatermark('/path/to/logo.png', opacity: 0.18, widthMm: 40.0)
->imageWatermarkPosition('top_left')
```

### Combined positions

```php
->watermarkPositions('center', 'bottom_right');
```

---

## Encryption, PDF/A, and metadata

Requires **FPDI + TCPDF**. These steps **re-import** the rasterized PDF and generally **strip** original PDF link annotations (see note below for the encryption + links case).

### `encrypt`

```php
->encrypt(
    userPassword: 'open',
    ownerPassword: 'owner',
    blockedPermissions: ['print'], // or null for TCPDF defaults
    algorithm: 3                   // 0–3, AES-256 = 3
);
```

### `pdfA`

```php
->pdfA(true);  // or int level per TCPDF PDF/A support
```

Cannot be combined with `encrypt()` in the same rebuild options (library enforces pipeline rules).

### `documentMetadata`

Sets PDF Info fields where supported:

```php
->documentMetadata(
    title: 'Invoice #12',
    author: 'Acme Ltd',
    subject: 'April 2026',
    keywords: 'invoice, pdf',
);
```

---

## Merge and reprocess

### `ChromePdf::merge`

Static helper: concatenate PDFs from **paths** and/or **`PdfOutput`** instances.

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

$merged = ChromePdf::merge([
    'chapter1.pdf',
    $somePdfOutput,
    storage_path('app/chapter3.pdf'),
]);
$merged->save('storage/app/manual.pdf');
```

### `reprocessPdf`

Run the **current builder’s** document options (watermark / encrypt / PDF-A / metadata, etc.) on an existing PDF **without** Chromium.

```php
$out = ChromePdf::create()
    ->encrypt('secret')
    ->reprocessPdf('input.pdf');
```

---

## Links, encryption, and TCPDF rebuild

- **`clickableLinks(true)`** (default): Chromium emits link annotations in the PDF.
- **`encrypt(...)`** (with a user password) rebuilds the document in TCPDF, which would normally drop those annotations.
- When **both** are enabled, ChromePdf collects link geometry in the headless page and **re-applies** TCPDF `Link()` regions after encryption so anchors stay usable where the pipeline supports it.

Watermark-only DOM paths can avoid a full rebuild and keep native Chromium links.

---

## `generate()` and `PdfOutput`

```php
/** @var \Tamedevelopers\Support\ChromePdf\PdfOutput $output */
$output = ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();
```

### `PdfOutput` methods

| Method | Purpose |
|--------|---------|
| `getContents(): string` | Raw PDF bytes |
| `save(string $path): array` | Writes to disk (creates directories); returns `path`, `url`, `name`, `storage` keys |
| `download(string $filename = 'document.pdf'): void` | `Content-Disposition: attachment` |
| `inline(string $filename = 'document.pdf'): void` | Inline display, normal cache headers |
| `view(string $filename = 'preview.pdf'): void` | Inline with stricter no-cache headers (handy while iterating) |

```php
$bytes = $output->getContents();
file_put_contents('out.pdf', $bytes); // equivalent to save(), without helper metadata

$output->save('storage/app/report.pdf');
$output->download('report.pdf');
```

---

## Shutdown browser

A **shared** Chromium instance is reused across `generate()` calls for performance.

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

ChromePdf::shutdown();
```

Also registered on PHP shutdown in normal long-lived scripts; call explicitly in workers/daemons when PDF work is done.

---

## Exceptions

- **`Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException`** — invalid input, timeouts, Chromium failures, merge/reprocess errors, streaming when headers already sent, etc.
- **`Tamedevelopers\Support\ChromePdf\Exception\FontNotFoundException`** — font resolution failures where applicable.

---

## License

Custom License (Source-Available)  
Copyright (c) 2026 Tamedevelopers
