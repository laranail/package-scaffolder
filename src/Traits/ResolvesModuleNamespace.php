<?php

namespace Simtabi\Laranail\Package\Scaffolder\Traits;

use Simtabi\Laranail\Package\Scaffolder\Module;

trait ResolvesModuleNamespace
{
    /**
     * Resolve a module's root namespace from its composer.json psr-4 autoload.
     *
     * This is the authoritative namespace classes in the module live under,
     * regardless of the configured default namespace or where the module sits
     * on disk (e.g. a custom scan path).
     */
    public function getModuleNamespace(Module $module): ?string
    {
        $psr4 = (array) data_get($module->getComposerAttr('autoload', []), 'psr-4', []);
        $appFolder = trim((string) config('modules.paths.app_folder', ''), '/');

        foreach ($psr4 as $namespace => $path) {
            // The module root maps to the app folder ('app/') or the module
            // root ('' or '.'), so this is the namespace classes live under.
            $cleaned = trim((string) $path, './');

            if ($cleaned === '' || $cleaned === $appFolder) {
                return rtrim($namespace, '\\');
            }
        }

        return null;
    }
}
