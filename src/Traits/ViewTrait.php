<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Capsule\File;

/** 
 * 
 * @property mixed $lockedShared
 * @property mixed $baseFolder
 * @method mixed render()
 */
trait ViewTrait{

    /**
     * Set shared data.
     */
    public static function setShared(string $key, mixed $value)
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Get shared data by key.
     */
    public static function getShared(string $key)
    {
        return self::$sharedData[$key] ?? null;
    }

    /**
     * Check if shared data exists.
     */
    public static function hasShared(string $key)
    {
        return isset(self::$sharedData[$key]);
    }

    /**
     * Remove shared data by key.
     */
    public static function removeShared(string $key)
    {
        unset(self::$sharedData[$key]);
    }

    /**
     * Merge multiple shared data items.
     */
    public static function mergeShared(array $data)
    {
        self::$sharedData = array_merge(self::$sharedData, $data);
    }

    /**
     * Clear all shared data.
     */
    public static function clearShared()
    {
        self::$sharedData = [];
    }

    /**
     * Get all shared data.
     */
    public static function allShared()
    {
        return self::$sharedData;
    }

    /**
     * Share data only once per request.
     */
    public static function shareOnce(string $key, mixed $value)
    {
        if (!isset(self::$sharedData[$key])) {
            self::$sharedData[$key] = $value;
        }
    }

    /**
     * Conditionally share data.
     */
    public static function shareIf(bool $condition, string $key, mixed $value)
    {
        if ($condition) {
            self::$sharedData[$key] = $value;
        }
    }

    /**
     * Prevent shared data from being overwritten.
     */
    public static function lockShared(string $key)
    {
        self::$lockedShared[$key] = true;
    }

    /**
     * Allow shared data to be overwritten.
     */
    public static function unlockShared(string $key)
    {
        unset(self::$lockedShared[$key]);
    }

    /**
     * Get the count of shared data items.
     */
    public static function sharedCount()
    {
        return count(self::$sharedData);
    }
    
    /**
     * Global shared data for all views.
     *
     * @var array
     */
    protected static $sharedData = [];
    
    /**
     * Share data globally across all views.
     *
     * @param string $key The key for the data.
     * @param mixed $value The value to be shared.
     * @return void
     */
    public static function share($key, $value)
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Get all shared data.
     *
     * @return array
     */
    public static function getSharedData()
    {
        return self::$sharedData;
    }

    /**
     * Resolve the full path to the view file.
     * @param string|null $viewPath
     * @return string The full path to the view file.
     */
    protected function resolveViewFilePath($viewPath = null)
    {
        // Work with a local target view (do not mutate $this->viewPath here)
        $targetView = empty($viewPath) ? $this->viewPath : $viewPath;

        // Prepend base folder only once if provided and not already prefixed
        $base = isset(self::$baseFolder) ? trim((string) self::$baseFolder, ". /") : '';
        if ($base !== '') {
            $baseDot = $base . '.';
            $startsWithBase = (strpos($targetView, $baseDot) === 0) || ($targetView === $base);
            if (!$startsWithBase) {
                $targetView = $baseDot . ltrim((string) $targetView, '.');
            }
        }

        // Normalize slashes and trim leading slash
        $normalizedView = str_replace('.', '/', ltrim((string) $targetView, '/'));

        // Try multiple base candidates to be resilient in different environments
        $candidates = array_unique(array_filter([
            rtrim((string) $this->basePath, '/')
        ]));

        foreach ($candidates as $basePath) {
            $filePath = $basePath . '/' . $normalizedView;
            $files = [
                $filePath . '.php',
                $filePath . '.blade.php',
                $filePath . '.twig',
                $filePath . '.html.twig',
            ];

            foreach ($files as $file) {
                if (File::exists($file)) {
                    return $file;
                }
            }
        }

        // Fallback to first candidate .php path (even if not exists)
        $firstBase = reset($candidates) ?: '';
        return $firstBase . '/' . $normalizedView . '.php';
    }

    /**
     * Read the raw view file contents (no execution).
     *
     * @param string $viewFilePath The path to the view file.
     * @return string The raw contents of the view file.
     */
    protected function renderViewFile($viewFilePath)
    {
        return File::exists($viewFilePath) ? (string) File::get($viewFilePath) : '';
    }

    /**
     * Parse @if, @elseif, @else, @endif directives
     */
    protected function parseDirectiveIf(string $content): string
    {
        $content = preg_replace('/@if\s*\((.+?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif ($1): ?>', $content);
        $content = str_replace('@else', '<?php else: ?>', $content);
        $content = str_replace('@endif', '<?php endif; ?>', $content);
        return $content;
    }

    /**
     * Parse @for, @endfor directives
     */
    protected function parseDirectiveFor(string $content): string
    {
        $content = preg_replace('/@for\s*\((.+?)\)/', '<?php for ($1): ?>', $content);
        $content = str_replace('@endfor', '<?php endfor; ?>', $content);
        return $content;
    }

    /**
     * Parse @foreach, @endforeach directives
     */
    protected function parseDirectiveForeach(string $content): string
    {
        $content = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach ($1): ?>', $content);
        $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);
        return $content;
    }

    /**
     * Parse @while, @endwhile directives
     */
    protected function parseDirectiveWhile(string $content): string
    {
        $content = preg_replace('/@while\s*\((.+?)\)/', '<?php while ($1): ?>', $content);
        $content = str_replace('@endwhile', '<?php endwhile; ?>', $content);
        return $content;
    }

    /**
     * Parse @yield directive
     */
    protected function parseDirectiveYield(string $content): string
    {
        return preg_replace('/@yield\s*\((.+?)\)/', '<?php echo $this->yieldSection($1); ?>', $content);
    }

    /**
     * Parse @section and @endsection directives
     */
    protected function parseDirectiveSection(string $content): string
    {
        // Inline form: @section('title', 'Homepage')
        $content = preg_replace(
            "/@section\s*\(\s*([\'\"][^\'\"]+[\'\"])\s*,\s*(.*?)\s*\)/",
            '<?php $this->section($1, $2); ?>',
            $content
        );

        // Block form: @section('content') ... @endsection
        $content = preg_replace('/@section\s*\((.+?)\)/', '<?php $this->startSection($1); ?>', $content);
        $content = str_replace('@endsection', '<?php $this->endSection(); ?>', $content);
        return $content;
    }

    /**
     * Parse raw PHP blocks: @php and @endphp
     */
    protected function parseDirectivePhp(string $content): string
    {
        $content = str_replace('@php', '<?php', $content);
        $content = str_replace('@endphp', '?>', $content);
        return $content;
    }

    /**
     * Parse escaped output: {{ $variable }}
     */
    protected function parseDirectiveEscaped(string $content): string
    {
        return preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>', $content);
    }

    /**
     * Parse unescaped output: {!! $variable !!}
     */
    protected function parseDirectiveUnescaped(string $content): string
    {
        return preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
    }

    /**
     * Parse @include directive in a simpler, readable way.
     * Supports:
     *  - @include('path.to.view')
     *  - @include('path.to.view', ['key' => $value])
     * Renders the included view with its own data only (no inheritance/merge).
     */
    protected function parseDirectiveInclude(string $content): string
    {
        // Generate a simple runtime call so it's easy to test and read
        return preg_replace('/@include\s*\((.*?)\)/s', '<?php echo $this->renderInclude($1); ?>', $content);
    }

    /**
     * Parse @extends directive.
     * Usage: @extends('layout.path')
     * Stores the parent layout to be rendered after child sections are captured.
     */
    protected function parseDirectiveExtends(string $content): string
    {
        return preg_replace('/@extends\s*\((.*?)\)/', '<?php $this->setParentView($1); ?>', $content);
    }

    /**
     * Remove Blade-style comments: {{-- ... --}}
     */
    protected function parseDirectiveComments(string $content): string
    {
        return preg_replace('/\{\{\-\-.*?\-\-\}\}/s', '', $content);
    }

    /**
     * Set the parent view for layout inheritance.
     * @param mixed $view
     * @return void
     */
    protected function setParentView($view)
    {
        $this->parentView = $view;
    }

    /**
     * Render an include at runtime. This is small, testable and readable.
     * @param mixed $view The view identifier (string/expression)
     * @param array $array Optional data array
     * @return string
     */
    protected function renderInclude($view, $array = [])
    {
        // Ensure included views inherit current data unless explicitly overridden
        $payload = empty($array) ? $this->data : array_merge($this->data, (array) $array);

        return (new static($view, $payload))->render();
    }

    /**
     * Start a section buffer (used by @section('name'))
     * @param string $name
     * @return void
     */
    protected function startSection($name)
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    /**
     * End the current section and store buffered content (used by @endsection)
     * @return void
     */
    protected function endSection()
    {
        $content = ob_get_clean();
        $name = array_pop($this->sectionStack);
        if (!isset($this->data['sections'])) {
            $this->data['sections'] = [];
        }
        // Append if the section was defined multiple times
        $this->data['sections'][$name] = ($this->data['sections'][$name] ?? '') . $content;
    }

    /**
     * Parse Directives
     *
     * @param  mixed $content
     * @return string
     */
    protected function parseDirectives($content)
    {
        // 1) Remove comments first so nothing inside is parsed
        $content = $this->parseDirectiveComments($content);

        // 2) Extends should be parsed early so parent is set before sections render
        $content = $this->parseDirectiveExtends($content);

        // 3) Control structures and others
        $content = $this->parseDirectiveIf($content);
        $content = $this->parseDirectiveForeach($content);
        $content = $this->parseDirectiveFor($content);
        $content = $this->parseDirectiveWhile($content);
        $content = $this->parseDirectiveYield($content);
        $content = $this->parseDirectiveSection($content);
        $content = $this->parseDirectivePhp($content);
        $content = $this->parseDirectiveEscaped($content);
        $content = $this->parseDirectiveUnescaped($content);
        $content = $this->parseDirectiveInclude($content);
        
        return $content;
    }

}
