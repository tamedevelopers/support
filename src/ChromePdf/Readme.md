# ChromePdf

High-performance **HTML → PDF generator for PHP** powered by **Headless Chromium** using `chrome-php/chrome`.

ChromePdf allows you to convert **URLs, HTML strings, or local HTML files** into PDFs using a fluent and developer-friendly API while preserving real browser rendering.

---

## Documentation

* [Requirements](#requirements)
* [Installation](#installation)
* [Enable PHP Extensions](#enable-php-extensions)
* [Development Environment](#development-environment)
* [Installing Chromium Binary](#installing-chromium-binary-if-chrome-is-not-installed)
    * [Download Chromium For Windows](#download-chromium-for-windows)
    * [Installation on Linux](#installation-on-linux-terminal)
    * [Example Folder Structure](#example-folder-structure)
* [Basic Usage](#basic-usage)
* [Sources](#sources)
    * [fromUrl](#fromurl)
    * [fromHtml](#fromhtml)
    * [fromFile](#fromfile)
* [Page Settings](#page-settings)
    * [paper](#paper)
    * [landscape](#landscape)
    * [margins](#margins)
* [Rendering Options](#rendering-options)
    * [prioritizeSpeed](#prioritizespeed)
    * [maximumQuality](#maximumquality)
    * [loadRemoteImages](#loadremoteimages)
    * [waitForWindowLoadEvent](#waitforwindowloadevent)
* [DOM Processing](#dom-processing)
    * [selectElement](#selectelement)
    * [removeCookiePopups](#removecookiepopups)
    * [clickableLinks](#clickablelinks)
* [Styling](#styling)
    * [css](#css)
    * [cssFile](#cssfile)
* [Color Scheme](#color-scheme)
* [Chromium Settings](#chromium-settings)
    * [chromiumBinary](#chromiumbinary)
    * [ignoreCertificateErrors](#ignorecertificateerrors)
* [Generate PDF](#generate-pdf)
* [Output Options](#output-options)
* [Shutdown Browser](#shutdown-browser)

---

# Requirements

- PHP **8.1+**
- Google Chrome or Chromium installed
- Composer
- PHP **Sockets Extension**

Required packages (to be installed):

```json
{
  "require": {
    "chrome-php/chrome": "^1.11",
    "chrome-php/wrench": "^1.7",
    "ext-sockets": "*"
  }
}
```

---

# Installation

Install via Composer.

```bash
composer require tamedevelopers/support
```

---

# Enable PHP Extensions

ChromePdf communicates with Chromium through WebSockets.  
Therefore the **sockets extension must be enabled**.

### php.ini

```
;extension=sockets
```

Change to:

```
extension=sockets
```

Restart your web server afterward.

This applies to:

- XAMPP
- WAMP
- MAMP
- Linux PHP
- macOS PHP

---

# Development Environment

If you are developing locally and **Google Chrome is already installed on your system**, you **do not need to download or configure a Chromium binary manually**.

ChromePdf will automatically detect Chrome from common system locations.

### Supported Automatic Detection

**Windows**

```
C:\Program Files\Google\Chrome\Application\chrome.exe
C:\Program Files (x86)\Google\Chrome\Application\chrome.exe
```

**Linux**

```
/usr/bin/google-chrome
/usr/bin/google-chrome-stable
/usr/bin/chromium
/usr/bin/chromium-browser
```

**macOS**

```
/Applications/Google Chrome.app/Contents/MacOS/Google Chrome
/Applications/Chromium.app/Contents/MacOS/Chromium
```

If Chrome exists in one of these locations, **ChromePdf will use it automatically**, so no additional configuration is required.

You only need to download and configure a Chromium binary when:

- Chrome is **not installed**
- You are running on **shared hosting**
- You want to use a **custom Chromium build**

---

# Installing Chromium Binary (If Chrome Is Not Installed)
- ChromePdf requires **Google Chrome or Chromium** to generate PDFs.
- If Chrome is not installed on your server (for example **shared hosting or minimal VPS environments**), you can download a **portable Chromium binary** and configure ChromePdf to use it.

| Platform | Binary |
|----------|--------|
| Windows  | `chrome.exe` |
| Linux | `chromium` or `google-chrome` |
| macOS | `Google Chrome` |

## Download Chromium For Windows

You can download Chromium from:
- https://download-chromium.appspot.com/ (Click downloadchromium)
- https://chromium.woolyss.com/download/ (Download the Zip and not .exe file)

Choose the correct binary for your platform.
```
```
---

## Installation on Linux (Terminal)

```bash

wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install -y ./google-chrome-stable_current_amd64.deb


Verify the installation
google-chrome-stable --version


Check path
which google-chrome-stable



/usr/bin/google-chrome-stable, 
```

## Example Folder Structure
Place the binary anywhere you want inside your project:

### Configure ChromePdf
Specify the binary path:

```php
ChromePdf::create()
    ->chromiumBinary(base_path('/bin/chromium/chrome.exe'));
```

Linux example:
```php
ChromePdf::create()
    ->chromiumBinary('/home/user/bin/chromium/chrome');
```

---
## Important Notes

- The server must allow **exec() and proc_open()**
- Minimum recommended RAM: **512MB+**
- Some shared hosting providers may block browser execution

---

# Basic Usage

```php
use Tamedevelopers\Support\ChromePdf\ChromePdf;

$output = ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();

$output->view();
```

---

# Full Example

```php
$output = ChromePdf::create()
    // ->fromHtml('<html><body><p>你好世界</p></body></html>')
    ->fromFile('invoice/template.html')
    ->paper('A4')
    ->selectElement('.body')
    ->margins(20)
    ->clickableLinks(false)
    ->generate();


$output->inline();

// $output->view();
// $output->download();
// $output->save('invoice/invoice.pdf'); 
```

---
# Sources

## fromUrl

Convert a website to PDF.

```php
ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();
```

---

## fromHtml

Convert raw HTML.

```php
ChromePdf::create()
    ->fromHtml('<h1>Hello World</h1>')
    ->generate();
```

Supports Unicode content.

```php
ChromePdf::create()
    ->fromHtml('<html><body><p>你好世界</p></body></html>')
    ->generate();
```

---

## fromFile

Convert a local HTML file.

```php
ChromePdf::create()
    ->fromFile('/path/to/file.html')
    ->generate();
```

---

# Page Settings

## paper

Set the PDF page format.

```php
ChromePdf::create()
    ->paper('A4');
```

Supported formats:

- A0
- A1
- A2
- A3
- A4
- A5
- A6
- B4
- B5
- Letter
- Legal
- Ledger
- Tabloid
- Executive

---

## landscape

Enable landscape orientation.

```php
ChromePdf::create()
    ->landscape();
```

---

## margins

Control PDF margins.

```php
ChromePdf::create()
    ->margins(20);
```

Example units:

```
10mm
1cm
0.5in
```

---

# Rendering Options

## prioritizeSpeed

Optimize rendering for speed.

```php
ChromePdf::create()
    ->prioritizeSpeed();
```

---

## maximumQuality

Render with full resources.

```php
ChromePdf::create()
    ->maximumQuality();
```

---

## loadRemoteImages

Enable external images.

```php
ChromePdf::create()
    ->loadRemoteImages(true);
```

---

## waitForWindowLoadEvent

Wait for full browser load.

```php
ChromePdf::create()
    ->waitForWindowLoadEvent(true);
```

---

# DOM Processing

## selectElement

Render only a specific element.

```php
ChromePdf::create()
    ->selectElement('#invoice');
```

---

## removeCookiePopups

Remove cookie banners automatically.

```php
ChromePdf::create()
    ->removeCookiePopups(true);
```

---

## clickableLinks

Enable or disable clickable links.

```php
ChromePdf::create()
    ->clickableLinks(false);
```

---

# Styling

## css

Inject custom CSS.

```php
ChromePdf::create()
    ->css('body { font-size: 28px; font-weight: bold; }');
```

---

## cssFile

Load CSS file.

```php
ChromePdf::create()
    ->cssFile('/path/to/style.css');
```

---

# Color Scheme

```php
ChromePdf::create()
    ->colorScheme('dark');
```

Options:

- light
- dark
- no-preference

---

# Chromium Settings

## chromiumBinary

```php
ChromePdf::create()
    ->chromiumBinary('/usr/bin/chromium');
```

---

## ignoreCertificateErrors

```php
ChromePdf::create()
    ->ignoreCertificateErrors();
```

---

# Generate PDF

```php
$output = ChromePdf::create()
    ->fromUrl('https://example.com')
    ->generate();
```

---

# Output Options

### View in Browser

```php
$output->view();
```

### Download

```php
$output->download();
```

### Save to File

```php
file_put_contents('file.pdf', $output->get());
```

---

# Shutdown Browser

ChromePdf reuses a shared Chromium instance.

```php
ChromePdf::shutdown();
```

---

# License

MIT License