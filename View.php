<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\ViewTrait;


/**
 * View Wrapper
 */ 
class View{

    use ViewTrait;
    
    /**
     * The path to the view file.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * The base path to the view directory.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The data to be passed to the view.
     *
     * @var array
     */
    protected $data;
    
    /**
     * The data to be passed to the view.
     *
     * @var array
     */
    protected static $directives = [];
    protected static $namespaces = [];
    protected static $sharedData = [];
    protected static $renderStack = [];

    /**
     * Parent layout view (set by @extends)
     * @var string|null
     */
    protected $parentView = null;



    /**
     * Create a new View instance.
     *
     * @param string|null $viewPath The path to the view file.
     * @param array $data The data to be passed to the view.
     */
    public function __construct($viewPath = null, $data = [])
    {
        $this->basePath = base_path();
        $this->viewPath = $viewPath;
        $this->data     = array_merge(self::getSharedData(), $data);
    }

    /**
     * Handle static method calls.
     *
     * @param string $methodName The name of the called method.
     * @param array $arguments The arguments passed to the method.
     * @return mixed
     */
    public static function __callStatic($methodName, $arguments)
    {
        if ($methodName === 'render') {
            return (new self(...$arguments))->render();
        }
    }

    /**
     * Render the view file.
     *
     * @return string The rendered content of the view file.
     * @throws Exception If the view file is not found or recursion detected.
     */
    public function render()
    {
        try {
            $viewFilePath = $this->resolveViewFilePath();
            $normalizedPath = realpath($viewFilePath) ?: $viewFilePath;

            // Recursion protection: prevent rendering the same template in the same stack
            if (in_array($normalizedPath, self::$renderStack)) {
                throw new Exception("Infinite recursion detected while rendering view: $normalizedPath");
            }

            // Push the current view onto the render stack
            self::$renderStack[] = $normalizedPath;

            // Check if the view file doesn't exist
            if (!File::exists($viewFilePath)) {
                array_pop(self::$renderStack);
                return "<!-- View file not found: $viewFilePath -->";
            }

            // Read raw template and parse directives to PHP
            $raw = $this->renderViewFile($viewFilePath);
            $parsedContent = $this->parseDirectives($raw);

            // Evaluate parsed PHP with current data
            extract($this->data);
            ob_start();
            $__prev_reporting = error_reporting();
            error_reporting($__prev_reporting & ~E_WARNING & ~E_NOTICE & ~E_USER_WARNING & ~E_USER_NOTICE);
            eval('?>' . $parsedContent);
            $childOutput = ob_get_clean();
            error_reporting($__prev_reporting);

            // If extends was set during evaluation, render the parent now
            if (!empty($this->parentView)) {
                if (!isset($this->data['sections']['content'])) {
                    $this->data['sections']['content'] = $childOutput;
                }
                $output = (new self($this->parentView, $this->data))->render();
            } else {
                $output = $childOutput;
            }

            array_pop(self::$renderStack);
            
            return $output;
        } catch (Exception $e) {
            // Clean up the render stack in case of error
            if (!empty(self::$renderStack)) {
                array_pop(self::$renderStack);
            }
            
            return "<!-- Error rendering view: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }

    /**
     * Check if a view file exists.
     *
     * @param string $view The name/path of the view/template.
     * @return bool
     */
    public static function exists($view)
    {
        $self = new self();

        return File::exists(
            $self->resolveViewFilePath($view)
        );
    }

    /**
     * Share data across all views.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function share($key, $value)
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Get shared data by key.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function getShared($key)
    {
        $data = self::getSharedData();
        return $data[$key] ?? null;
    }

    /**
     * Remove all shared data.
     */
    public static function flushShared()
    {
        self::$sharedData = [];
    }

    /**
     * Attach a callback to a view before rendering (composer).
     *
     * @param string $template
     * @param callable $callback
     */
    public static function composer($template, callable $callback)
    {
        if (self::exists($template)) {
            $callback(new self($template));
        }
    }

    /**
     * Add a namespace for view lookup.
     *
     * @param string $namespace
     * @param string $path
     */
    public static function addNamespace($namespace, $path)
    {
        // Implementation depends on your view system
        // Example: store namespaces in a static property
        self::$namespaces[$namespace] = $path;
    }

    /**
     * Cache rendered views for performance.
     *
     * @param string $template
     * @param array $data
     * @param int $ttl
     * @return string
     */
    public static function cache($template, $data, $ttl = 60)
    {
        $key = md5($template . serialize($data));
        $cacheFile = sys_get_temp_dir() . "/view_cache_{$key}.php";
        if (File::exists($cacheFile) && (filemtime($cacheFile) + $ttl) > time()) {
            return File::get($cacheFile);
        }
        $content = (new self($template, $data))->render();

        File::put($cacheFile, $content);
        
        return $content;
    }

    /**
     * Conditionally render a view.
     *
     * @param bool $condition
     * @param string $template
     * @param array $data
     * @return string|null
     */
    public static function renderIf($condition, $template, $data = [])
    {
        return $condition ? (new self($template, $data))->render() : null;
    }

    /**
     * Render a view for each item in a data array.
     *
     * @param string $template
     * @param array $dataArray
     * @return string
     */
    public static function renderEach($template, $dataArray)
    {
        $output = '';
        foreach ($dataArray as $data) {
            $output .= (new self($template, $data))->render();
        }
        return $output;
    }

    /**
     * Pass error messages to the view.
     *
     * @param array $errors
     * @return $this
     */
    public function withErrors(array $errors)
    {
        $this->data['errors'] = $errors;
        return $this;
    }

    /**
     * Pass old form input to the view.
     *
     * @param array $input
     * @return $this
     */
    public function withOldInput(array $input)
    {
        $this->data['old'] = $input;
        return $this;
    }

    /**
     * Render a partial view.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public static function renderPartial($template, $data = [])
    {
        return (new self($template, $data))->render();
    }

    /**
     * Set a layout for the view.
     *
     * @param string $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->data['layout'] = $layout;
        return $this;
    }

    /**
     * Define a section for layouts.
     *
     * @param string $name
     * @param string $content
     */
    public function section($name, $content)
    {
        $this->data['sections'][$name] = $content;
    }

    /**
     * Output a section in a layout.
     *
     * @param string $name
     * @return string|null
     */
    public function yieldSection($name)
    {
        return $this->data['sections'][$name] ?? null;
    }

    /**
     * Add global variables accessible in all views.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function addGlobal($key, $value)
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Add custom directives for template engine.
     *
     * @param string $name
     * @param callable $callback
     */
    public static function registerDirective($name, callable $callback)
    {
        self::$directives[$name] = $callback;
    }

}