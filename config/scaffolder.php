<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Scaffolder Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Scaffolder SCAFFOLDING operations.
    | Access via: config('scaffolder.*')
    | This config is NEVER used by Package runtime operations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Project Configuration
    |--------------------------------------------------------------------------
    |
    | These settings define your project's base configuration. The scaffolder
    | uses these values to generate proper namespaces, tags, and paths.
    |
    */
    'project' => [
        // Root namespace of your application (e.g., 'App', 'MyCompany\MyApp')
        'namespace' => env('SCAFFOLDER_NAMESPACE', 'App'),

        // Prefix for publish tags (e.g., 'app', 'mycompany')
        'tag_prefix' => env('SCAFFOLDER_TAG_PREFIX', 'app'),

        // Vendor name from composer.json (e.g., 'vendor')
        'vendor' => env('SCAFFOLDER_VENDOR', 'vendor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Path Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the directory structure for scaffolding operations.
    | Supports standard Laravel apps, modular apps, and monorepo structures.
    |
    */
    'paths' => [
        // Stubs directory for templates
        'stubs' => env('SCAFFOLDER_STUBS_PATH', base_path('stubs')),

        // Output directory for generated packages
        'output' => env('SCAFFOLDER_OUTPUT_PATH', 'packages'),

        // Modules directory (e.g., 'modules', 'app/Modules')
        'modules' => env('SCAFFOLDER_MODULES_PATH', 'modules'),

        // Packages directory (e.g., 'packages', 'platform/packages')
        'packages' => env('SCAFFOLDER_PACKAGES_PATH', 'packages'),

        // Base path for resolution (defaults to Laravel base_path())
        'base' => env('SCAFFOLDER_BASE_PATH', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Conventions
    |--------------------------------------------------------------------------
    |
    | Define the namespace structure for different component types when
    | scaffolding. These are relative to the project namespace.
    |
    */
    'namespaces' => [
        // View components namespace (e.g., 'View\Components')
        'view_components' => 'View\\Components',

        // Livewire components namespace (e.g., 'Http\Livewire')
        'livewire' => 'Http\\Livewire',

        // Controllers namespace (e.g., 'Http\Controllers')
        'controllers' => 'Http\\Controllers',

        // Models namespace (e.g., 'Models')
        'models' => 'Models',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pattern Configuration
    |--------------------------------------------------------------------------
    |
    | Define patterns for generating dynamic values during scaffolding.
    | Available variables:
    | {project}      - Project namespace from config
    | {module}       - Module name (StudlyCase)
    | {module_kebab} - Module name (kebab-case)
    | {prefix}       - Tag prefix from config
    | {vendor}       - Vendor name from config
    | {type}         - Component/resource type
    | {component_ns} - Component namespace from config
    |
    */
    'patterns' => [
        // Component namespace pattern
        // Example: 'App\Blog\View\Components'
        'component_namespace' => '{project}\\{module}\\{component_ns}',

        // Publish tag pattern
        // Example: 'app-blog-views'
        'publish_tag' => '{prefix}-{module_kebab}-{type}',

        // Livewire component alias pattern
        // Example: 'blog::posts.index'
        'livewire_alias' => '{module_kebab}::{name}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Package Type
    |--------------------------------------------------------------------------
    |
    | Default package type when scaffolding new packages.
    | Options: 'full', 'minimal', 'api', 'both'
    |
    */
    'default_type' => env('SCAFFOLDER_DEFAULT_TYPE', 'both'),

    /*
    |--------------------------------------------------------------------------
    | Default Author
    |--------------------------------------------------------------------------
    |
    | Default author information for generated packages.
    |
    */
    'default_author' => [
        'name' => env('SCAFFOLDER_AUTHOR_NAME', 'Package Author'),
        'email' => env('SCAFFOLDER_AUTHOR_EMAIL', 'author@example.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scaffolding Features
    |--------------------------------------------------------------------------
    |
    | Features to include when scaffolding packages.
    | These control what gets scaffolded, NOT runtime behavior.
    |
    */
    'features' => [
        'vite' => true,
        'tailwind' => true,
        'pest' => true,
        'phpstan' => true,
        'github_actions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Defaults
    |--------------------------------------------------------------------------
    |
    | Default values used when generating new packages.
    |
    */
    'defaults' => [
        'license' => 'MIT',
        'php_version' => '^8.3',
        'laravel_version' => '^12.0',
        'description' => 'A Laravel package',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Detection
    |--------------------------------------------------------------------------
    |
    | Enable automatic detection of project configuration from composer.json.
    | When enabled, the scaffolder will attempt to detect your project's
    | namespace, vendor name, and structure automatically.
    |
    */
    'auto_detect' => [
        'enabled' => env('SCAFFOLDER_AUTO_DETECT_ENABLED', true),

        // Paths to check for PSR-4 namespace detection
        'psr4_paths' => ['app/', 'src/'],
    ],
];
