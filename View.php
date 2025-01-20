<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Traits\SharedDataTrait;


/**
 * View Wrapper
 */ 
class View{

    use SharedDataTrait;
    
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
     * Create a new View instance.
     *
     * @param string $viewPath The path to the view file.
     * @param array $data The data to be passed to the view.
     */
    public function __construct($viewPath, $data = [])
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
     * @throws Exception If the view file is not found.
     */
    public function render()
    {
        $viewFilePath = $this->resolveViewFilePath();

        if (!file_exists($viewFilePath)) {
            throw new Exception("View file not found: $viewFilePath");
        }
        
        // Render the raw content of the view file
        $content = $this->renderViewFile($viewFilePath);

        // Parse custom directives in the content
        $parsedContent = $this->parseDirectives($content);
        
        // Extract variables into the current scope
        extract($this->data);

        // Evaluate the parsed PHP code
        ob_start();
        eval('?>' . $parsedContent);
        return ob_get_clean();
    }

    /**
     * Resolve the full path to the view file.
     *
     * @return string The full path to the view file.
     */
    protected function resolveViewFilePath()
    {
        $basePath = rtrim($this->basePath, '/');
        $viewPath = ltrim($this->viewPath, '/');
        $viewPath = str_replace('.', '/', $this->viewPath);

        return $basePath . '/' . $viewPath . '.php';
    }

    /**
     * Render the view file and capture the output.
     *
     * @param string $viewFilePath The path to the view file.
     * @return string The captured output of the view file.
     */
    protected function renderViewFile($viewFilePath)
    {
        ob_start();
        extract($this->data);
        include $viewFilePath;
        return ob_get_clean();
    }

}