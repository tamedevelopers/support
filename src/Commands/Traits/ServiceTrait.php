<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Logger;


trait ServiceTrait{

    /**
     * Parse and normalize the user input into usable parts.
     *
     * @return array
     */
    protected function parseInput(): array
    {
        $name = str_replace('\\', '/', $this->argument('name')); // normalize slashes
        $segments = explode('/', $name);

        $className    = Str::studly(array_pop($segments));
        $relativePath = implode('/', $segments);

        $baseNamespace = 'App\\Services';
        $namespace     = $baseNamespace . ($relativePath ? '\\' . str_replace('/', '\\', $relativePath) : '');

        $basePath  = app_path('Services');
        $directory = $basePath . ($relativePath ? '/' . $relativePath : '');
        $filePath  = $directory . '/' . $className . '.php';

        return [$className, $namespace, $filePath, $directory];
    }

    /**
     * Determine if the service file already exists.
     *
     * @param  string $filePath
     * @return bool
     */
    protected function serviceExists(string $filePath): bool
    {
        if (File::exists($filePath)) {
            Logger::error("Service already exists at: $filePath");
            return true;
        }

        return false;
    }

    /**
     * Ensure the directory exists or create it if missing.
     *
     * @param  string $directory
     * @return void
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Build the service class stub content.
     *
     * @param  string $className
     * @param  string $namespace
     * @return string
     */
    protected function buildClassStub(string $className, string $namespace): string
    {
        return <<<PHP
            <?php

            namespace {$namespace};
            
            class {$className}
            {
                /**
                 * Public constructor
                 */
                public function __construct()
                {
                    // 
                }

            }
            
            PHP;
    }

}