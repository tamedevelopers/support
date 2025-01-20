<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;


trait SharedDataTrait
{
    
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
     * Parse Directives
     *
     * @param  mixed $content
     * @return void
     */
    protected function parseDirectives($content)
    {
        // Parse @if, @elseif, @else, @endif
        $content = preg_replace('/@if\s*\((.+?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif ($1): ?>', $content);
        $content = str_replace('@else', '<?php else: ?>', $content);
        $content = str_replace('@endif', '<?php endif; ?>', $content);

        // Parse @foreach, @endforeach
        $content = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach ($1): ?>', $content);
        $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);

        // Parse @for, @endfor
        $content = preg_replace('/@for\s*\((.+?)\)/', '<?php for ($1): ?>', $content);
        $content = str_replace('@endfor', '<?php endfor; ?>', $content);

        // Parse @while, @endwhile
        $content = preg_replace('/@while\s*\((.+?)\)/', '<?php while ($1): ?>', $content);
        $content = str_replace('@endwhile', '<?php endwhile; ?>', $content);

        // Parse @include
        $content = preg_replace_callback('/@include\s*\((.+?)\)/', function ($matches) {
            $view = trim($matches[1], '\'"');
            return "<?php echo (new self('$view', get_defined_vars()))->render(); ?>";
        }, $content);

        // Parse @yield
        $content = preg_replace('/@yield\s*\((.+?)\)/', '<?php echo $this->yieldSection($1); ?>', $content);

        // Parse @section and @endsection
        $content = preg_replace('/@section\s*\((.+?)\)/', '<?php $this->startSection($1); ?>', $content);
        $content = str_replace('@endsection', '<?php $this->endSection(); ?>', $content);

        // Parse raw PHP blocks: @php and @endphp
        $content = str_replace('@php', '<?php', $content);
        $content = str_replace('@endphp', '?>', $content);

        // Parse escaped output: {{ $variable }}
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);

        // Parse unescaped output: {!! $variable !!}
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);

        return $content;
    }

}
