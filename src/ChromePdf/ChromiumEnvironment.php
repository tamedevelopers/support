<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf;

use HeadlessChromium\AutoDiscover;

/**
 * Detects runtime environment (Docker vs host) and resolves the Chromium/Chrome binary.
 */
final class ChromiumEnvironment
{
    public function isDocker(): bool
    {
        if (@is_file(base_path('.dockerenv'))) {
            return true;
        }

        if (!@is_readable('/proc/self/cgroup')) {
            return false;
        }

        $cgroup = @file_get_contents('/proc/self/cgroup');

        return is_string($cgroup) && str_contains($cgroup, 'docker');
    }

    public function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    public function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }

    public function isDarwin(): bool
    {
        return PHP_OS_FAMILY === 'Darwin';
    }

    public function isWindowAndNotDocker(): bool
    {
        return $this->isWindows() && !$this->isDocker();
    }

    /**
     * @return array<string, mixed>
     */
    public function getLaunchOptions(): array
    {
        $options = [
            'headless' => true,
            // Fail fast; chrome-php default is 30s — avoid long stalls when Chrome is missing or blocked
            'startupTimeout' => 30,
            'customFlags' => [
                '--disable-background-networking',
                '--no-first-run',
                '--disable-extensions',
                '--mute-audio',
                '--disable-background-timer-throttling',
                '--disable-renderer-backgrounding',
                '--disable-backgrounding-occluded-windows',
                // Allow file:// assets referenced by local HTML rendered via setHtml()+<base href="file://...">.
                '--allow-file-access-from-files',
            ],
        ];

        if ($this->isDocker()) {
            $options['noSandbox'] = true;
            // Shared-memory pressure in containers; headless already passes --disable-gpu
            $options['customFlags'][] = '--disable-dev-shm-usage';
        }

        return $options;
    }

    /**
     * Returns an executable path, or null to let {@see \HeadlessChromium\BrowserFactory} auto-discover.
     */
    public function resolveChromeBinary(): ?string
    {
        $fromEnv = getenv('CHROME_PATH') ?: getenv('CHROMIUM_PATH');
        if (is_string($fromEnv) && $fromEnv !== '' && $this->isExecutablePath($fromEnv)) {
            return $fromEnv;
        }

        $candidates = [];

        if ($this->isDocker()) {
            $candidates = [
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
            ];
        } elseif ($this->isWindows()) {
            $candidates = [
                getenv('ProgramFiles') . '\\Google\\Chrome\\Application\\chrome.exe',
                getenv('ProgramFiles(x86)') . '\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            ];
        } elseif ($this->isDarwin()) {
            $candidates = [
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
                '/Applications/Chromium.app/Contents/MacOS/Chromium',
            ];
        } else {
            $candidates = [
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium',
                '/usr/bin/chromium-browser',
            ];
        }

        foreach ($candidates as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            if ($this->isExecutablePath($path)) {
                return $path;
            }
        }

        $guessed = (new AutoDiscover())->guessChromeBinaryPath();
        if ($this->isExecutablePath($guessed)) {
            return $guessed;
        }

        return null;
    }

    private function isExecutablePath(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        return is_executable($path);
    }
}
