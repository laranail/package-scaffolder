<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Support;

use Illuminate\Support\Str;

/**
 * PlaceholderResolver - Resolves template placeholders for package generation
 *
 * Provides comprehensive placeholder resolution for stub/template files.
 * Supports multiple naming conventions and formats.
 */
class PlaceholderResolver
{
    protected string $vendor;

    protected string $package;

    protected array $additionalData;

    protected array $resolved = [];

    public function __construct(string $vendor, string $package, array $additionalData = [])
    {
        $this->vendor = $vendor;
        $this->package = $package;
        $this->additionalData = $additionalData;
    }

    /**
     * Get all resolved placeholders
     *
     * @return array<string, string>
     */
    public function resolve(): array
    {
        if (! empty($this->resolved)) {
            return $this->resolved;
        }

        $this->resolved = array_merge(
            $this->resolveVendorPlaceholders(),
            $this->resolvePackagePlaceholders(),
            $this->resolveVendorPackagePlaceholders(),
            $this->resolveNamespacePlaceholders(),
            $this->resolveRoutePlaceholders(),
            $this->resolveDatabasePlaceholders(),
            $this->resolveComposerPlaceholders(),
            $this->resolveAdditionalPlaceholders()
        );

        return $this->resolved;
    }

    /**
     * Resolve vendor-related placeholders
     */
    protected function resolveVendorPlaceholders(): array
    {
        return [
            '$VENDOR_NAME$' => Str::studly($this->vendor),
            '$VENDOR_STUDLY$' => Str::studly($this->vendor),
            '$VENDOR_KEBAB$' => Str::kebab($this->vendor),
            '$VENDOR_SNAKE$' => Str::snake($this->vendor),
            '$VENDOR_CAMEL$' => Str::camel($this->vendor),
            '$VENDOR_LOWER$' => Str::lower($this->vendor),
            '$VENDOR_UPPER$' => Str::upper($this->vendor),
            '$VENDOR_TITLE$' => Str::title($this->vendor),
        ];
    }

    /**
     * Resolve package-related placeholders
     */
    protected function resolvePackagePlaceholders(): array
    {
        return [
            '$PACKAGE_NAME$' => Str::studly($this->package),
            '$PACKAGE_STUDLY$' => Str::studly($this->package),
            '$PACKAGE_KEBAB$' => Str::kebab($this->package),
            '$PACKAGE_SNAKE$' => Str::snake($this->package),
            '$PACKAGE_CAMEL$' => Str::camel($this->package),
            '$PACKAGE_LOWER$' => Str::lower($this->package),
            '$PACKAGE_UPPER$' => Str::upper($this->package),
            '$PACKAGE_TITLE$' => Str::title($this->package),
            '$PACKAGE_PLURAL$' => Str::plural(Str::studly($this->package)),
            '$PACKAGE_SINGULAR$' => Str::singular(Str::studly($this->package)),

            // Singular variants
            '$PACKAGE_STUDLY_SINGULAR$' => Str::singular(Str::studly($this->package)),
            '$PACKAGE_KEBAB_SINGULAR$' => Str::singular(Str::kebab($this->package)),
            '$PACKAGE_SNAKE_SINGULAR$' => Str::singular(Str::snake($this->package)),
            '$PACKAGE_CAMEL_SINGULAR$' => Str::singular(Str::camel($this->package)),
            '$PACKAGE_TITLE_SINGULAR$' => Str::singular(Str::title($this->package)),
        ];
    }

    /**
     * Resolve vendor/package combined placeholders
     *
     * Enforces vendor/package pattern for Laravel standards
     */
    protected function resolveVendorPackagePlaceholders(): array
    {
        $vendorKebab = Str::kebab($this->vendor);
        $packageKebab = Str::kebab($this->package);
        $packageSnake = Str::snake($this->package);

        return [
            // Vendor/Package namespace patterns (for views, translations)
            '$VENDOR_PACKAGE_NAMESPACE$' => "{$vendorKebab}/{$packageKebab}",
            '$VENDOR_PACKAGE_KEBAB$' => "{$vendorKebab}-{$packageKebab}",
            '$VENDOR_PACKAGE_SNAKE$' => Str::snake($vendorKebab).'_'.$packageSnake,
            '$VENDOR_PACKAGE_STUDLY$' => Str::studly($vendorKebab).Str::studly($packageKebab),

            // Helper function name (single fluent entry point)
            '$HELPER_FUNCTION_NAME$' => "package_{$packageSnake}",

            // Helper function prefix (legacy support - for migration)
            '$HELPER_PREFIX$' => "package_{$packageSnake}",

            // View namespace (Laravel standard: vendor/package) - ENFORCED
            '$VIEW_NAMESPACE$' => "{$vendorKebab}/{$packageKebab}",

            // Translation namespace (Laravel standard: vendor/package) - ENFORCED
            '$TRANSLATION_NAMESPACE$' => "{$vendorKebab}/{$packageKebab}",

            // Config namespace (configurable: simple or vendor/package)
            '$CONFIG_NAMESPACE$' => $this->getConfigNamespace(),

            // Publish tag prefix (Laravel standard: vendor::package) - ENFORCED
            '$PUBLISH_TAG_PREFIX$' => "{$vendorKebab}::{$packageKebab}",
        ];
    }

    /**
     * Resolve namespace-related placeholders
     */
    protected function resolveNamespacePlaceholders(): array
    {
        $vendorStudly = Str::studly($this->vendor);
        $packageStudly = Str::studly($this->package);

        return [
            '$NAMESPACE$' => "{$vendorStudly}\\{$packageStudly}",
            '$ROOT_NAMESPACE$' => "{$vendorStudly}\\{$packageStudly}",
            '$ROOT_CLASS_NAMESPACE$' => "{$vendorStudly}\\{$packageStudly}",
            '$ROOT_COMPOSER_NAMESPACE$' => "{$vendorStudly}\\\\{$packageStudly}\\\\",
            '$PSR4_NAMESPACE$' => "{$vendorStudly}\\\\{$packageStudly}\\\\",
        ];
    }

    /**
     * Resolve route-related placeholders
     */
    protected function resolveRoutePlaceholders(): array
    {
        $kebab = Str::kebab($this->package);
        $routePrefix = $this->additionalData['route_prefix'] ?? 'packages';
        $isSingular = $this->additionalData['singular_route_prefix'] ?? false;

        $prefix = $isSingular ? Str::singular($routePrefix) : $routePrefix;

        return [
            '$ROUTE_PREFIX$' => "{$prefix}/{$kebab}",
            '$ROUTE_NAME$' => "{$prefix}.{$kebab}",
            '$ROUTE_PREFIX_SINGULAR$' => Str::singular($routePrefix)."/{$kebab}",
            '$ROUTE_NAME_SINGULAR$' => Str::singular($routePrefix).".{$kebab}",
        ];
    }

    /**
     * Resolve database-related placeholders
     */
    protected function resolveDatabasePlaceholders(): array
    {
        return [
            '$MODEL_NAME$' => Str::singular(Str::studly($this->package)),
            '$TABLE_NAME$' => Str::plural(Str::snake($this->package)),
            '$TABLE_NAME_SINGULAR$' => Str::singular(Str::snake($this->package)),
            '$MIGRATION_CLASS$' => 'Create'.Str::plural(Str::studly($this->package)).'Table',
            '$FACTORY_NAME$' => Str::singular(Str::studly($this->package)).'Factory',
            '$SEEDER_NAME$' => Str::studly($this->package).'Seeder',
        ];
    }

    /**
     * Resolve composer-related placeholders
     */
    protected function resolveComposerPlaceholders(): array
    {
        $vendorKebab = Str::kebab($this->vendor);
        $packageKebab = Str::kebab($this->package);

        return [
            '$COMPOSER_NAME$' => "{$vendorKebab}/{$packageKebab}",
            '$COMPOSER_VENDOR$' => $vendorKebab,
            '$COMPOSER_PACKAGE$' => $packageKebab,
            '$DESCRIPTION$' => $this->additionalData['description'] ?? "Package description for {$packageKebab}",
            '$LICENSE$' => $this->additionalData['license'] ?? 'MIT',
        ];
    }

    /**
     * Resolve additional custom placeholders
     */
    protected function resolveAdditionalPlaceholders(): array
    {
        $placeholders = [
            '$CLASS_NAME$' => Str::studly($this->package),
            '$PROPERTY_NAME$' => Str::camel($this->package),
            '$CONSTANT_NAME$' => Str::upper(Str::snake($this->package)),
            '$BLADE_PREFIX$' => Str::kebab($this->package),
            '$VIEW_NAMESPACE$' => Str::kebab($this->package),
        ];

        // Author information
        if (isset($this->additionalData['author'])) {
            $author = $this->additionalData['author'];
            $placeholders['$AUTHOR_NAME$'] = $author['name'] ?? '';
            $placeholders['$AUTHOR_EMAIL$'] = $author['email'] ?? '';
            $placeholders['$AUTHOR_HOMEPAGE$'] = $author['homepage'] ?? '';
            $placeholders['$AUTHOR_ROLE$'] = $author['role'] ?? 'Developer';
        }

        // Current year and date
        $placeholders['$YEAR$'] = date('Y');
        $placeholders['$DATE$'] = date('Y-m-d');
        $placeholders['$DATETIME$'] = date('Y-m-d H:i:s');

        return $placeholders;
    }

    /**
     * Replace placeholders in content
     *
     * @param  string  $content  Content with placeholders
     * @return string Content with resolved placeholders
     */
    public function replace(string $content): string
    {
        $placeholders = $this->resolve();

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $content
        );
    }

    /**
     * Get a specific placeholder value
     *
     * @param  string  $placeholder  Placeholder name (with or without $ symbols)
     */
    public function get(string $placeholder): ?string
    {
        // Normalize placeholder format
        $placeholder = '$'.trim($placeholder, '$').'$';

        $placeholders = $this->resolve();

        return $placeholders[$placeholder] ?? null;
    }

    /**
     * Check if a placeholder exists
     *
     * @param  string  $placeholder  Placeholder name
     */
    public function has(string $placeholder): bool
    {
        return $this->get($placeholder) !== null;
    }

    /**
     * Static factory method
     */
    public static function make(string $vendor, string $package, array $additionalData = []): static
    {
        return new static($vendor, $package, $additionalData);
    }

    /**
     * Get all available placeholders as a list
     */
    public function available(): array
    {
        return array_keys($this->resolve());
    }

    /**
     * Get config namespace based on style preference
     */
    protected function getConfigNamespace(): string
    {
        $style = $this->additionalData['config_namespace_style'] ?? 'simple';

        if ($style === 'vendor-package') {
            return Str::kebab($this->vendor).'/'.Str::kebab($this->package);
        }

        // Default: simple (package name only)
        return Str::kebab($this->package);
    }
}
