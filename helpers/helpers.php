<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Simtabi\Laranail\Package\Scaffolder\Exceptions\ModuleNotFoundException;
use Simtabi\Laranail\Package\Scaffolder\FileRepository;
use Simtabi\Laranail\Package\Scaffolder\Module;

if (! function_exists('module')) {
    /**
     * Retrieves a module status or its instance.
     *
     * @param  string  $name  The name of the module.
     * @param  bool  $instance  Whether to return the module's instance instead of the status. Defaults to false [status].
     * @return bool|Module The module instance or its status.
     */
    function module(string $name, bool $instance = false): bool|Module
    {
        /** @var FileRepository $repository */
        $repository = app('modules');

        try {
            $module = $repository->findOrFail($name);

            return $instance ? $module : $module->isEnabled();
        } catch (ModuleNotFoundException $exception) {
            return false;
        }
    }
}

if (! function_exists('module_path')) {
    function module_path(string $name, string $path = ''): string
    {
        $module = app('modules')->find($name);

        if ($module === null) {
            // The module registry may not be resolved yet (e.g. early bootstrap
            // or constrained runtimes like NativePHP). Fall back to the
            // configured modules path so callers get a sane path instead of a
            // fatal "getPath() on null".
            $base = config('modules.paths.modules', base_path('Modules')).DIRECTORY_SEPARATOR.$name;

            return $base.($path ? DIRECTORY_SEPARATOR.$path : $path);
        }

        return $module->getPath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     */
    function config_path(string $path = ''): string
    {
        return app()->basePath().'/config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     */
    function public_path(string $path = ''): string
    {
        return app()->make('path.public').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
    }
}

if (! function_exists('module_vite')) {
    /**
     * support for vite
     */
    function module_vite(string $module, string $asset, ?string $hotFilePath = null): Vite
    {
        return ViteFacade::useHotFile($hotFilePath ?: storage_path('vite.hot'))->useBuildDirectory($module)->withEntryPoints([$asset]);
    }
}
