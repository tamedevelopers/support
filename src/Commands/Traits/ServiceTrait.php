<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Installer;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Logger;


trait ServiceTrait{

    /**
     * Parse and normalize the user input into usable parts.
     * 
     * @param string|null $passedName
     * @return array
     */
    protected function parseInput($passedName = null): array
    {
        $name = str_replace('\\', '/', $passedName ?? $this->argument('name')); // normalize slashes
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
     * Ensure the directory exists or create it if missing.
     */
    protected function createBaseService(): void
    {
        $service = Installer::getPathsData(null, 'service');

        $dummyDir   = realpath(__DIR__ . '/../../');
        $dummyPath  = Tame::stringReplacer("{$dummyDir}{$service['dummy']}");
        $filePath   = $service['path'];
        
        if(File::exists($dummyPath) && !File::exists($filePath)){
            // Read the contents of the dummy file
            $dummyContent = File::get($dummyPath);

            // Write the contents to the new file
            File::put($filePath, $dummyContent);
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

            use App\Services\BaseService;
            
            class {$className} extends BaseService
            {
                // 

            }
            
            PHP;
    }

    /**
     * Build the service class stub content with Resource support
     *
     * @param  string $className
     * @param  string $namespace
     * @return string
     */
    protected function buildClassStubWithResource(string $className, string $namespace): string
    {
        return <<<PHP
            <?php

            namespace {$namespace};
            
            use App\Services\BaseService;
            
            class {$className} extends BaseService
            {
                /**
                 * Store something in the service.
                 *
                 * @param  array \$param
                 * @param  \Tamedevelopers\Validator\Validator \$response
                 * @return array
                 */
                public function store(\$param, \$response)
                {
                    //
                }
                
                /**
                 * Update something in the service.
                 *
                 * @param  array \$param
                 * @param  \Tamedevelopers\Validator\Validator \$response
                 * @return array
                 */
                public function update(\$param, \$response)
                {
                    //
                }
                
                /**
                 * Delete something in the service.
                 *
                 * @param  array \$param
                 * @param  \Tamedevelopers\Validator\Validator \$response
                 * @return array
                 */
                public function delete(\$param, \$response)
                {
                    //
                }
                
            }
            
            PHP;
    }

}