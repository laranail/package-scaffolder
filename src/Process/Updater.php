<?php

namespace Simtabi\Laranail\Package\Scaffolder\Process;

use Simtabi\Laranail\Package\Scaffolder\Support\Module;

class Updater extends Runner
{
    /**
     * Update the dependencies for the specified module by given the module name.
     */
    public function update(string $module)
    {
        $module = $this->module->findOrFail($module);

        chdir(base_path());

        $this->installRequires($module);
        $this->installDevRequires($module);
        $this->copyScriptsToMainComposerJson($module);
    }

    /**
     * Check if composer should output anything.
     */
    private function isComposerSilenced(): string
    {
        return config('modules.composer.composer-output') === false ? ' --quiet' : '';
    }

    private function installRequires(Module $module)
    {
        $concatenatedPackages = $this->concatPackages($module->getComposerAttr('require', []));

        if ($concatenatedPackages !== '') {
            $this->run("composer require {$concatenatedPackages}{$this->isComposerSilenced()}");
        }
    }

    private function installDevRequires(Module $module)
    {
        $concatenatedPackages = $this->concatPackages($module->getComposerAttr('require-dev', []));

        if ($concatenatedPackages !== '') {
            $this->run("composer require --dev {$concatenatedPackages}{$this->isComposerSilenced()}");
        }
    }

    /**
     * Build a shell-safe, space-separated list of `name:version` specs. Each spec
     * is escaped so an untrusted module's composer metadata cannot inject shell
     * syntax into the `composer require` command.
     *
     * @param  array<string, string>  $packages
     */
    private function concatPackages(array $packages): string
    {
        $out = '';
        foreach ($packages as $name => $version) {
            $out .= escapeshellarg("{$name}:{$version}").' ';
        }

        return trim($out) === '' ? '' : $out;
    }

    private function copyScriptsToMainComposerJson(Module $module)
    {
        $scripts = $module->getComposerAttr('scripts', []);

        if (empty($scripts)) {
            return;
        }

        $path = base_path('composer.json');
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Unable to read host composer.json at [{$path}].");
        }

        $composer = json_decode($raw, true);
        if (! is_array($composer)) {
            throw new \RuntimeException("Host composer.json at [{$path}] is not valid JSON; refusing to modify it.");
        }

        $composer['scripts'] ??= [];

        foreach ($scripts as $key => $script) {
            if (array_key_exists($key, $composer['scripts'])) {
                $composer['scripts'][$key] = array_values(array_unique(array_merge(
                    (array) $composer['scripts'][$key],
                    (array) $script,
                )));

                continue;
            }
            $composer['scripts'][$key] = $script;
        }

        $encoded = json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new \RuntimeException("Failed to encode host composer.json at [{$path}].");
        }

        // Atomic write: temp file + rename, so an interrupted write can't corrupt
        // the developer's root composer.json.
        $tmp = $path.'.tmp'.getmypid();
        file_put_contents($tmp, $encoded);
        rename($tmp, $path);
    }
}
