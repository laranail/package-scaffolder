<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

/**
 * The Boost guidance under `resources/boost/` tells AI agents which config keys
 * to read, which config files to edit and which publish tags to use. Every one
 * it cites must exist in the booted application: the config repository, the
 * publish map's destinations, and `ServiceProvider::publishableGroups()`.
 * Config keys moved to `laranail.package-scaffolder.*` in 0.1.0 with no
 * fallback, so a stale key in the guidance silently reads null.
 */
class BoostGuidanceConfigReferencesTest extends BaseTestCase
{
    private const string MODULES_KEY = 'laranail.package-scaffolder.modules';

    /** Measured 2026-10-05: 1 guideline + SKILL.md + 5 rule files. */
    private const int FILE_FLOOR = 7;

    /** Measured 2026-10-05: package config keys + config file paths + publish tags cited. */
    private const int REFERENCE_FLOOR = 14;

    /**
     * The guidance also shows a generated module's OWN config — `config('blog.per_page')`,
     * `config('{vendor}.{name}.*')`, a module's `config/config.php` — which belongs to the
     * host application and does not exist in this package's test app. Only references in
     * this package's namespace are checked: `laranail.*`, plus the pre-0.1.0 bare roots
     * `modules` / `artifacts`, so a stale key or file name is caught rather than skipped.
     */
    private const string PACKAGE_KEY = '/^(laranail|modules|artifacts)(\.|$)/';

    private const string PACKAGE_PATH = '#^(laranail/|modules\.php$|artifacts\.php$)#';

    /** `config('key')`, but not `Module::config('key')`. */
    private const string CONFIG_KEY = '/(?<![\w:>])config\(\s*[\'"]([^\'"]+)[\'"]/';

    /** `Module::config('key')` reads below `laranail.package-scaffolder.modules`. */
    private const string MODULE_CONFIG_KEY = '/Module::config\(\s*[\'"]([^\'"]+)[\'"]/';

    /** A config file path such as `config/laranail/package-scaffolder/modules.php`. */
    private const string CONFIG_PATH = '#(?<![\w/])config/([\w./-]+\.php)#';

    /** `--tag=x`, `--tag="x"` or `--tag='x'`. */
    private const string PUBLISH_TAG = '/--tag=[\'"]?([^\s\'"`]+)/';

    public function test_every_config_reference_the_guidance_cites_exists_in_the_booted_app(): void
    {
        $files = $this->guidanceFiles();
        $this->assertGreaterThanOrEqual(self::FILE_FLOOR, count($files));

        $config = $this->app['config'];
        $destinations = array_values(ServiceProvider::pathsToPublish());
        $groups = array_keys(ServiceProvider::$publishGroups);
        $references = 0;

        foreach ($files as $file) {
            $where = $file->getRelativePathname();
            $text = $file->getContents();

            preg_match_all(self::CONFIG_KEY, $text, $keys);
            foreach (preg_grep(self::PACKAGE_KEY, $keys[1]) as $key) {
                $references++;
                $this->assertTrue($config->has($key), "{$where} cites config('{$key}'), which the booted app does not have.");
            }

            preg_match_all(self::MODULE_CONFIG_KEY, $text, $moduleKeys);
            foreach ($moduleKeys[1] as $key) {
                $references++;
                $full = self::MODULES_KEY . '.' . $key;
                $this->assertTrue($config->has($full), "{$where} cites Module::config('{$key}'), which reads [{$full}], which does not exist.");
            }

            preg_match_all(self::CONFIG_PATH, $text, $paths);
            foreach (preg_grep(self::PACKAGE_PATH, $paths[1]) as $path) {
                $references++;
                $published = array_filter($destinations, static fn (string $d): bool => str_ends_with(str_replace('\\', '/', $d), '/config/' . $path));
                $this->assertNotEmpty($published, "{$where} cites config/{$path}, which no publish tag writes.");
            }

            preg_match_all(self::PUBLISH_TAG, $text, $tags);
            foreach ($tags[1] as $tag) {
                $references++;
                $this->assertContains($tag, $groups, "{$where} cites publish tag [{$tag}], which is not registered.");
            }
        }

        $this->assertGreaterThanOrEqual(self::REFERENCE_FLOOR, $references);
    }

    /** @return list<SplFileInfo> */
    private function guidanceFiles(): array
    {
        $finder = Finder::create()->files()->in(dirname(__DIR__, 2) . '/resources/boost');

        return array_values(iterator_to_array($finder, false));
    }
}
