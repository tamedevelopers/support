<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\QRCustomOptions;
use Tamedevelopers\Support\QRCustomSvgRenderer;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Time;
use Tamedevelopers\Support\Traits\QRCodeOptionTrait;
use Tamedevelopers\Support\Traits\QRCodeTrait;

class QRCode
{
    use QRCodeTrait, QRCodeOptionTrait;

    /** @var string|null Target file output path */
    protected ?string $path = null;

    /** @var int QR version (1-40) */
    protected int $version = Version::AUTO;

    /** @var bool Image transparency */
    protected bool $transparent = false;

    /** @var int Error correction level constant */
    protected int $eccLevel = EccLevel::H;

    /** @var int Pixel scaling multiplier */
    protected int $scale = 10;

    /** @var mixed Rendered data */
    protected mixed $data = null;

    /** @var string|null Configured QR payload string */
    protected ?string $payload = null;

    /** @var string Selected pattern preset */
    protected string $pattern = 'classic';

    /** @var string Selected template preset */
    protected string $template = 'default';

    /** @var bool Whether to overlay the logo icon in center */
    protected bool $showIcon = true;

    /** @var string|null Detected social platform name */
    protected ?string $platform = null;

    /** @var string|null Custon Icon Path */
    protected ?string $iconPath = null;

    /** @var array{
     *  facebook: string,
     *  messenger: string,
     *  instagram: string,
     *  linkedin: string,
     *  whatsapp: string,
     *  youtube: string,
     *  spotify: string,
     *  google: string,
     *  telegram: string,
     *  wechat: string,
     *  discord: string,
     *  snapchat: string,
     *  tiktok: string,
     *  playstore: string,
     *  appstore: string,
     * } Standard social network base URLs */
    protected array $socialPlatforms = [
        'facebook'  => 'https://facebook.com/',
        'messenger' => 'https://m.me/',
        'instagram' => 'https://instagram.com/',
        'linkedin'  => 'https://linkedin.com/in/',
        'whatsapp'  => 'https://wa.me/',
        'youtube'   => 'https://youtube.com/',
        'spotify'   => 'https://open.spotify.com/user/',
        'google'    => 'https://google.com/',
        'telegram'  => 'https://t.me/',
        'wechat'    => 'weixin://dl/chat?',
        'discord'   => 'https://discord.gg/',
        'snapchat'  => 'https://snapchat.com/add/',
        'tiktok'    => 'https://tiktok.com/@',
        'playstore' => 'https://market.android.com/details?id=',
        'appstore'  => 'https://itunes.apple.com/app/',
    ];

    /**
     * Constructor accepts optional target path.
     * 
     * @param string|null $path
     */
    public function __construct(?string $path = null)
    {
        $this->path = $path;
    }

    /**
     * Set the output file path.
     * 
     * @param string $path
     * @return self
     */
    public function path(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set custom logo icon path.
     * 
     * @param string $iconPath
     * @return self
     */
    public function iconPath(string $iconPath): self
    {
        $this->iconPath = $iconPath;
        return $this;
    }

    /**
     * Set Transparency
     * 
     * @param bool $transparent
     * @return self
     */
    public function transparent($transparent): self
    {
        $this->transparent = $transparent;
        return $this;
    }

    /**
     * Configure image output options fluently.
     * 
     * @param int $scale
     * @param int $version
     * @param int $eccLevel
     * @return self
     */
    public function options(int $scale = 10, int $version = Version::AUTO, int $eccLevel = EccLevel::H)
    {
        $this->scale = $scale;
        $this->version = $version;
        $this->eccLevel = $eccLevel;

        return $this;
    }

    /**
     * Set module shapes and optional color.
     * 
     * @param 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop' $shape
     * @param string|null $color Hex color code or CSS color
     * @return self
     */
    public function shape(string $shape, ?string $color = null): self
    {
        $this->shape = [
            'shape' => Str::lower($shape),
            'color' => $color,
        ];

        return $this;
    }

    /**
     * Set outer finder pattern shape and optional color.
     * 
     * @param 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop' $outerShape
     * @param string|null $color Hex color code or CSS color
     * @return self
     */
    public function outerShape(string $outerShape, ?string $color = null): self
    {
        $this->outerShape = [
            'shape' => Str::lower($outerShape),
            'color' => $color,
        ];

        return $this;
    }

    /**
     * Set inner finder pattern shape and optional color.
     * 
     * @param 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop' $innerShape
     * @param string|null $color Hex color code or CSS color
     * @return self
     */
    public function innerShape(string $innerShape, ?string $color = null): self
    {
        $this->innerShape = [
            'shape' => Str::lower($innerShape),
            'color' => $color,
        ];

        return $this;
    }

    /**
     * Set output pattern mode.
     * 
     * @param 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop' $pattern
     * @return self
     */
    public function pattern(string $pattern)
    {
        $this->pattern = $this->resolvePatterns($pattern);
        
        return $this;
    }

    /**
     * Set output template mode.
     * 
     * @param 'default'|'neon'|'forest'|'instagram'|'facebook'|'youtube'|'whatsapp'|'telegram'|'snapchat' $template
     * @return self
     */
    public function template(string $template)
    {
        $this->template = $this->resolveTemplates($template);
        
        return $this;
    }

    /**
     * Enable or disable overlaying the center icon.
     * 
     * @param bool $show
     * @return self
     */
    public function showIcon(bool $show = true)
    {
        $this->showIcon = $show;
        return $this;
    }

    /**
     * Set payload: Plain URL
     * 
     * @param string $url
     * @return self
     */
    public function addUrl(string $url)
    {
        $this->payload = $url;
        return $this;
    }

    /**
     * Set payload: Plain Text
     * 
     * @param string $text
     * @return self
     */
    public function addText(string $text)
    {
        $this->payload = $text;
        return $this;
    }

    /**
     * Set payload: Phone Call
     * 
     * @param string $phoneNumber
     * @return self
     */
    public function addPhone(string $phoneNumber)
    {
        $this->payload = "tel:{$phoneNumber}";
        return $this;
    }

    /**
     * Set payload: SMS Message
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return self
     */
    public function addSms(string $phoneNumber, string $message = '')
    {
        $encodedMessage = rawurlencode($message);
        $this->payload = !empty($message) ? "smsto:{$phoneNumber}:{$encodedMessage}" : "smsto:{$phoneNumber}";
        return $this;
    }

    /**
     * Set payload: Email
     * 
     * @param string $email
     * @param string $subject
     * @param string $body
     * @return self
     */
    public function addEmail(string $email, string $subject = '', string $body = '')
    {
        $params = [];
        if (!empty($subject)) $params['subject'] = $subject;
        if (!empty($body)) $params['body'] = $body;

        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        $this->payload = "mailto:{$email}{$queryString}";
        return $this;
    }

    /**
     * Set payload: vCard Contact Details
     * 
     * @param array{
     *   firstName?: string,
     *   lastName?: string,
     *   prefix?: string,
     *   title?: string,
     *   organization?: string,
     *   email?: string,
     *   mobile?: string,
     *   homePhone?: string,
     *   fax?: string,
     *   street?: string,
     *   city?: string,
     *   state?: string
     * } $data
     * @return self
     */
    public function addContact(array $data): self
    {
        $firstName    = $data['firstName'] ?? '';
        $lastName     = $data['lastName'] ?? '';
        $prefix       = $data['prefix'] ?? '';
        $title        = $data['title'] ?? '';
        $org          = $data['organization'] ?? '';
        $email        = $data['email'] ?? '';
        $mobile       = $data['mobile'] ?? '';
        $homePhone    = $data['homePhone'] ?? '';
        $fax          = $data['fax'] ?? '';
        $street       = $data['street'] ?? '';
        $city         = $data['city'] ?? '';
        $state        = $data['state'] ?? '';

        $formattedName = trim("{$prefix} {$firstName} {$lastName}");

        $vCard = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            "N:{$lastName};{$firstName};;{$prefix};",
            !empty($formattedName) ? "FN:{$formattedName}" : null,
            !empty($org) ? "ORG:{$org}" : null,
            !empty($title) ? "TITLE:{$title}" : null,
            !empty($mobile) ? "TEL;TYPE=CELL:{$mobile}" : null,
            !empty($homePhone) ? "TEL;TYPE=HOME:{$homePhone}" : null,
            !empty($fax) ? "TEL;TYPE=FAX:{$fax}" : null,
            !empty($email) ? "EMAIL:{$email}" : null,
            (!empty($street) || !empty($city) || !empty($state)) ? "ADR;TYPE=HOME:;;{$street};{$city};{$state};;;" : null,
            'END:VCARD'
        ];

        $this->payload = implode("\n", array_filter($vCard));
        return $this;
    }

    /**
     * Set payload: Social Profile or direct URL
     * 
     * @param string|'facebook'|'messenger'|'instagram'|'linkedin'|'whatsapp'|'youtube'|'spotify'|'google'|'telegram'|
     * 'wechat'|'discord'|'snapchat'|'tiktok'|'playstore'|'appstore' $platformOrUrl Platform identifier or full URL
     * @param string $identifier Username, ID, or phone number (e.g. for WhatsApp)
     * @return self
     */
    public function addSocial(string $platformOrUrl, string $identifier = ''): self
    {
        $platform = Str::lower($platformOrUrl);

        if (filter_var($platformOrUrl, FILTER_VALIDATE_URL)) {
            $this->payload = $platformOrUrl;
            // Attempt to resolve platform from full URL
            foreach ($this->socialPlatforms as $name => $url) {
                if (str_contains($platformOrUrl, $name)) {
                    $this->platform = $name;
                    break;
                }
            }
            return $this;
        }

        if (isset($this->socialPlatforms[$platform])) {
            $this->platform = $platform;
            $base           = $this->socialPlatforms[$platform];
            $identifier     = ltrim($identifier, '@');
            $this->payload  = $base . $identifier;
        } else {
            $this->payload = $identifier;
        }

        return $this;
    }

    /**
     * Set payload: File / Document Download URL
     * 
     * @param string $fileUrl
     * @return self
     */
    public function addFile(string $fileUrl): self
    {
        $this->payload = $fileUrl;
        return $this;
    }

    /**
     * Set payload: Multiple URLs or Links List
     * 
     * @param array<int, string> $urls List of URLs
     * @param string $separator Separator between links (defaults to newline)
     * @return self
     */
    public function addMultiUrl(array $urls, string $separator = "\n"): self
    {
        $filtered = array_filter(array_map('trim', $urls));
        $this->payload = implode($separator, $filtered);
        return $this;
    }

    /**
     * Set payload: PDF Document Link
     * 
     * @param string $pdfUrl Direct link to the PDF file
     * @return self
     */
    public function addPDF(string $pdfUrl): self
    {
        $this->payload = $pdfUrl;
        return $this;
    }

    /**
     * Set payload: Geographic Coordinates / Location Map
     * 
     * @param float|string $latitude
     * @param float|string $longitude
     * @return self
     */
    public function addLocation(float|string $latitude, float|string $longitude): self
    {
        $this->payload = "geo:{$latitude},{$longitude}";
        return $this;
    }

    /**
     * Set payload: Wi-Fi Network Credentials
     * 
     * @param string $ssid Network SSID
     * @param string $password Network Password
     * @param 'WPA'|'WEP'|'nopass'|string $encryption Encryption protocol
     * @param bool $hidden Whether the SSID network is hidden
     * @return self
     */
    public function addWifi(string $ssid, string $password = '', string $encryption = 'WPA', bool $hidden = false): self
    {
        // Escape special characters in SSID and password
        $escape = fn(string $value): string => addcslashes($value, ';;",:\\');

        $ssidEscaped     = $escape($ssid);
        $passwordEscaped = $escape($password);
        $encryptionUpper = strtoupper($encryption);
        $hiddenFlag      = $hidden ? 'true' : 'false';

        $this->payload = "WIFI:S:{$ssidEscaped};T:{$encryptionUpper};P:{$passwordEscaped};H:{$hiddenFlag};;";
        return $this;
    }

    /**
     * Set payload: Calendar Event (iCalendar / VEVENT)
     * 
     * @param array{
     *   title: string,
     *   start: string|Time|\DateTimeInterface,
     *   end?: string|Time|\DateTimeInterface,
     *   description?: string,
     *   location?: string
     * } $details
     * @return self
     */
    public function addEvent(array $details): self
    {
        $formatDate = function ($date): string {
            if ($date instanceof \DateTimeInterface) {
                return $date->format('Ymd\THis\Z');
            }

            return Time::parse($date)->format('Ymd\THis\Z');
        };

        $title       = $details['title'] ?? 'Event';
        $start       = isset($details['start']) ? $formatDate($details['start']) : '';
        $end         = isset($details['end']) ? $formatDate($details['end']) : $start;
        $description = $details['description'] ?? '';
        $location    = $details['location'] ?? '';

        $vEvent = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            "SUMMARY:{$title}",
            !empty($start) ? "DTSTART:{$start}" : null,
            !empty($end) ? "DTEND:{$end}" : null,
            !empty($description) ? "DESCRIPTION:{$description}" : null,
            !empty($location) ? "LOCATION:{$location}" : null,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $this->payload = implode("\n", array_filter($vEvent));
        return $this;
    }

    /**
     * Set payload: Cryptocurrency Payment URI
     * 
     * @param string $currency Cryptocurrency ticker/name (e.g. bitcoin, ethereum, usdt, litecoin, bitcoin-cash)
     * @param string $address Recipient wallet address
     * @param float|null $amount Optional requested amount
     * @param string|null $label Optional label or payment description
     * @return self
     */
    public function addCrypto(string $currency, string $address, ?float $amount = null, ?string $label = null): self
    {
        $scheme = match (Str::lower($currency)) {
            'bitcoin', 'btc'               => 'bitcoin',
            'ethereum', 'eth'              => 'ethereum',
            'litecoin', 'ltc'              => 'litecoin',
            'bitcoin-cash', 'bitcoing-cash', 'bch' => 'bitcoincash',
            'usdt', 'tether'               => 'usdt',
            'dogecoin', 'doge'             => 'dogecoin',
            'solana', 'sol'                => 'solana',
            default                        => Str::lower($currency),
        };

        $params = [];
        if ($amount !== null && $amount > 0) {
            $params['amount'] = $amount;
        }
        if (!empty($label)) {
            $params['label'] = $label;
        }

        $queryString   = !empty($params) ? '?' . http_build_query($params) : '';
        $this->payload = "{$scheme}:{$address}{$queryString}";

        return $this;
    }

    /**
     * Generate raw binary or SVG image data from QR string.
     * 
     * @param string|null $payload
     * @return string|null
     */
    protected function generate(?string $payload = null)
    {
        $content = $payload ?? $this->payload;

        if (empty($content)) {
            return null;
        }

        $options = new QRCustomOptions([
            'version'           => $this->version,
            'eccLevel'          => $this->eccLevel,
            'scale'             => $this->scale,
            'outputType'        => 'svg',
            'imageTransparent'  => $this->transparent,
            'paletteColors'     => $this->getPaletteColors(),
            'shape'             => $this->shape,
            'outerShape'        => $this->outerShape,
            'innerShape'        => $this->innerShape,
            'outputInterface'   => QRCustomSvgRenderer::class,
        ]);

        $qrcode = new ChillerlanQRCode($options);
        $matrix = $qrcode->addByteSegment($content)->getQRMatrix();

        $renderer  = new QRCustomSvgRenderer($options, $matrix);
        $svgOutput = $renderer->render();

        return $this->applyCenterIcon($svgOutput);
    }

    /**
     * Render the QR code and save to specified or stored path.
     */
    public function save(): bool
    {
        try {
            if (!empty($this->path)) {
                $outputType = 'svg';
                $extension  = File::extension($this->path);

                if (empty($extension)) {
                    $this->path = "{$this->path}.{$outputType}";
                } else {
                    $this->path = Str::replace($extension, $outputType, $this->path);
                }
            }

            $this->data = $this->generate();

            if (empty($this->data) || empty($this->path)) {
                return false;
            }

            return is_int(File::put($this->path, $this->data));
        } catch (\Throwable $e) {
            Manager::silentError($e);
        }

        return false;
    }

    /**
     * Get rendered data string or auto-generate if payload is set.
     */
    public function getData(): ?string
    {
        if (empty($this->data) && !empty($this->payload)) {
            $this->data = $this->generate();
        }

        return $this->data;
    }

    /**
     * Get the used path
     * 
     * @param 'name'|'extension'|'path' $mode
     * @return string
     */
    public function getPath(string $mode = 'path')
    {
        $path = $this->path ?: '';

        return match ($mode) {
            'extension' => File::extension($path),
            'name'      => File::base($path),
            default     => $path
        };
    }

    /**
     * Render as an HTML image element.
     * 
     * @param string|null $class
     * @return string
     */
    public function toImage(?string $class = null)
    {
        $data = $this->getData();

        if (empty($data)) {
            return '';
        }

        $base64Data = 'data:image/svg+xml;base64,' . base64_encode($data);
        $classAttr  = !empty($class) ? " class='{$class}'" : '';

        return "<img src='{$base64Data}'{$classAttr} alt='QR Code' width='500' height='500' />";
    }

    /**
     * Get QR Code as SVG markup or Base64 URI.
     * 
     * @param bool $base64 Whether to return as base64 string
     * @return string|null
     */
    public function toSvg(bool $base64 = false): ?string
    {
        $svg = $this->getData();

        if (empty($svg)) {
            return null;
        }

        return $base64 ? 'data:image/svg+xml;base64,' . base64_encode($svg) : $svg;
    }

    /**
     * Convert and return QR Code as PNG binary or Base64 URI.
     * 
     * @param bool $base64
     * @return string|null
     */
    public function toPng(bool $base64 = false): ?string
    {
        return $this->convertFormat('png', $base64);
    }

    /**
     * Convert and return QR Code as JPG binary or Base64 URI.
     * 
     * @param bool $base64
     * @return string|null
     */
    public function toJpg(bool $base64 = false): ?string
    {
        return $this->convertFormat('jpg', $base64);
    }

}