<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;

trait PathNamespace
{
    /**
     * Get a well-formatted StudlyCase representation of path components.
     */
    public function studly_path(string $path, $ds = '/'): string
    {
        return collect(explode($ds, $this->clean_path($path, $ds)))->map(fn ($path) => Str::studly($path))->implode($ds);
    }

    /**
     * Get a well-formatted StudlyCase namespace.
     */
    public function studly_namespace(string $namespace, $ds = '\\'): string
    {
        return $this->studly_path($namespace, $ds);
    }

    /**
     * Get a well-formatted namespace from a given path.
     */
    public function path_namespace(string $path): string
    {
        return Str::of($this->studly_path($path))->replace('/', '\\')->trim('\\');
    }

    /**
     * Get a well-formatted StudlyCase namespace for a module, with an optional additional path.
     */
    public function module_namespace(string $module, ?string $path = null): string
    {
        $module_namespace = config('modules.namespace', $this->path_namespace(config('modules.paths.modules'))).'\\'.($module);
        $module_namespace .= strlen($path) ? '\\'.$this->path_namespace($path) : '';

        return $this->studly_namespace($module_namespace);
    }

    /**
     * Clean path
     */
    public function clean_path(string $path, $ds = '/'): string
    {
        return Str::of($path)->explode($ds)->reject(empty($path))->implode($ds);
    }

    /**
     * Strip the configured app folder prefix from a generator path.
     *
     * `config('modules.paths.app_folder')` is a path prefix (e.g. "app/"), not
     * a character set. Removing it with `ltrim($path, $appFolder)` treats it as
     * a character mask, which corrupts any path whose next segment starts with
     * one of those characters (e.g. "app/api" -> "pi"). This removes it as a
     * proper prefix instead.
     */
    public function strip_app_folder(?string $path): string
    {
        $path = (string) $path;

        $appFolder = trim((string) config('modules.paths.app_folder', ''), '/');

        if ($appFolder === '') {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if ($normalized === $appFolder) {
            return '';
        }

        if (Str::startsWith($normalized, $appFolder.'/')) {
            return Str::after($normalized, $appFolder.'/');
        }

        return $path;
    }

    /**
     * Get the app path basename.
     */
    public function app_path(?string $path = null): string
    {
        $config_path = config('modules.paths.app_folder');

        // Get modules config app path or use Laravel default app path.
        $app_path = strlen($config_path) ? $config_path : 'app/';

        if ($path) {
            // Replace duplicate custom|default app paths
            $replaces = array_unique([$this->clean_path($app_path).'/', 'app/']);
            do {
                $path = Str::of($path)->replaceStart($app_path, '')->replaceStart('app/', '');
            } while (Str::of($path)->startsWith($replaces));

            // Append additional path
            $app_path .= strlen($path) ? '/'.$path : '';
        }

        return $this->clean_path($app_path);
    }
}
